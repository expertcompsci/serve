<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Respect\Validation\Validator as v;
use \DavidePastore\Slim\Validation\Validation;

use \Serve\Helpers\ResponseBody;
use \Serve\Helpers\AppErrors;
use \Serve\Exceptions\RowNotInsertedException;
use \Serve\Exceptions\RowNotFoundException;

$purposeValidator = v::length(0, 80);
$notesValidator = v::optional(v::length(1, 60000));

$searchPurposeValidator = v::optional(v::length(0, 80));
$searchNotesValidator =  v::optional(v::length(0, 80));
$idValidator = v::numeric()->positive();

$validators = array(
    'purpose' => $purposeValidator,
    'notes' => $notesValidator
);

$app->post('/upload-resume', function(Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $directory = $this->get('settings')['uploadDirectory'];
    $db = $this->get('db');
    $uploadedFiles = $request->getUploadedFiles();
    $uploadedFile = $uploadedFiles['resume'];   // Key is input control name attr
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $originalBasename = pathinfo($uploadedFile->getClientFilename(), PATHINFO_BASENAME);
        $basename = $originalBasename.'_'.bin2hex(random_bytes(8)).'_resume_';
        $filename = $basename.$extension;
        $filePath = $directory . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->moveTo($filePath);

        $blob = fopen($filePath, 'rb');
        $body = $request->getParsedBody();
        if(!empty($body['id'])) {
            $procName = "proc_commit_resume( :parameter_id,";
        } else {
            $procName = "proc_upload_resume(";
        }
        $sql = "CALL " . $procName . "
            :parameter_file_content, 
            :parameter_filename, 
            :parameter_original_basename, 
            :parameter_extension, 
            :parameter_purpose,
            :parameter_content_type,
            :parameter_size,
            :parameter_last_modified,
            :parameter_notes);";
        $stmt = $db->prepare($sql);
        if(!empty($body['id'])) {
            $stmt->bindParam(':parameter_id', $body['id'], PDO::PARAM_INT);
        }
        $stmt->bindParam(':parameter_file_content', $blob, PDO::PARAM_LOB);
        $stmt->bindValue(':parameter_filename', $filename);
        $stmt->bindValue(':parameter_original_basename', $originalBasename);
        $stmt->bindValue(':parameter_extension', $extension);
        $stmt->bindValue(':parameter_purpose', $body['purpose']);
        $stmt->bindValue(':parameter_content_type', $uploadedFile->getClientMediaType());
        $stmt->bindValue(':parameter_size', $uploadedFile->getSize());
        $stmt->bindValue(':parameter_last_modified', $body['lastModified']);
        $stmt->bindValue(':parameter_notes', $body['notes']);
        $stmtRet = $stmt->execute();
        fclose($blob);
        // If files accumulate it could be because #stmt->execute() was unsuccessful
        try {
            if($stmtRet) {
                unlink($filePath);
            };
            if($stmtRet === true){
                $res = ResponseBody::fromOkMessage('Uploaded resume.');
                return $response->withJson($res(), 201);
            } else {
                if($stmt->errorInfo()[0] != "00000") {
                    throw RowNotInsertedException::fromErrInfo($stmt->errorInfo(), $body['id']);
                }
                throw RowNotInsertedException::fromRowCount($stmt->rowCount());
            }
        } catch (Exception $e) {
            $res = ResponseBody::fromException($e);
            return $response->withJson($res(), 200);
        }
    }
})->add(new Validation($validators));

$app->post('/update-resume', function (Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = $request->getParsedBody();
    $stmt = $this->get('db')->prepare(
        "CALL proc_update_resume(
            :parameter_id,
            :parameter_purpose,
            :parameter_notes)"
    );
    $stmt->bindValue(':parameter_purpose', $body['purpose']);
    $stmt->bindValue(':parameter_notes', $body['notes']);
    $stmt->bindValue(':parameter_id', $body['id'], PDO::PARAM_INT);
    try {
        if(($stmt->execute() === true)){
            $res = ResponseBody::fromOkMessage('Updated resume.');
            return $response->withJson($res(), 200);
        } else {
            if($stmt->errorInfo()[0] != "00000") {
                throw RowNotUpdatedException::fromErrInfo($stmt->errorInfo(), $body['id']);
            }
            throw RowNotUpdatedException::fromId($body['id']);
        }
    } catch (Exception $e) {
        $res = ResponseBody::fromException($e);
        return $response->withJson($res(), 200);
    }
})->add(new Validation($validators))->add(new Validation(array('id' => $idValidator)));

$app->get('/search-resumes', function (Request $request, Response $response, array $args) {
    $appErrors = null;
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
    }
    $purpose = $request->getQueryParam('purpose');
    $notes = $request->getQueryParam('notes');
    $conditions = [];
    $parameters = [];
    if (!empty($purpose)){
        $conditions[] = 'purpose LIKE ?';
        $parameters[] = '%'.$purpose."%";
    }
    if (!empty($notes)){
        $conditions[] = 'notes LIKE ?';
        $parameters[] = '%'.$notes."%";
    }
    if(empty($conditions)) {
        if(empty($appErrors)){
            $appErrors = AppErrors::fromCodeTarget(AppErrors::errNoSearchTarg, 'purpose, notes');
        } else {
            $appErrors->addCodeTarget(AppErrors::errNoSearchTarg, 'purpose, notes');
        }
    }
    if(!empty($appErrors)){
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $sql = "SELECT * FROM resumes WHERE " . implode(" AND ", $conditions);
    $stmt = $this->get('db')->prepare($sql);        
    $stmt->execute($parameters);
    $rowSet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rowSet['rowCount'] = $stmt->rowCount();
    $res = ResponseBody::fromResultSet($rowSet);
    return $response->withJson($res());
})->add(new Validation(array('purpose' => $searchPurposeValidator, 'notes' => $searchNotesValidator)));

// Return resume meta-data, not the file or content
$app->get('/get-resume/{id}', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    // May be appropriate to include an existance check on associated file before returning
    $stmt = $this->get('db')->prepare("
        SELECT 
            id, 
            purpose,
            original_basename AS originalBasename,
            content_type AS contentType,
            last_modified AS lastModified,
            size,
            notes,
            created, 
            modified
        FROM resumes
        WHERE id = :parameter_id 
        order by modified");
    try {
        $stmt->bindValue(':parameter_id', $args['id']);
        $stmt->execute();
            if($stmt->rowCount() != 1) {
            throw RowNotFoundException::fromId($args['id']);
        }
        $res = ResponseBody::fromResultSet($stmt->fetchAll());
        return $response->withJson($res());
    } catch (Exception $e) {
        $res = ResponseBody::fromException($e);
        return $response->withJson($res(), 200);
    }
})->add(new Validation(array('id' => $idValidator)));

$app->delete('/delete-resume/{id}', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $stmt = $this->get('db')->prepare("CALL proc_delete_all_resume_revisions(:parameter_id);");
    try {
        $stmt->bindValue(':parameter_id', $args['id']);
        $stmt->execute();
        $res = ResponseBody::fromResultSet($stmt->fetchAll());
        return $response->withJson($res());
    } catch (Exception $e) {
        $res = ResponseBody::fromException($e);
        return $response->withJson($res(), 200);
    }
})->add(new Validation(array('id' => $idValidator)));

$app->get('/view-resume/{id}', function($request, Slim\Http\Response $response, $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $stmt = $this->get('db')->prepare("
        SELECT file_content, resumes.extension
        FROM resumes, files
        WHERE resumes.id = ? AND files.id = resumes.files_id;");
    $stmt->execute(array($args['id']));
    if($stmt->rowCount() != 1) {
        throw RowNotFoundException::fromId($args['id']);
    }
    $stmt->bindColumn(1, $lob, PDO::PARAM_LOB);
    $stmt->bindColumn(2, $ext);
    $stmt->fetch(PDO::FETCH_BOUND);
    switch($ext) {
        case "pdf":
            $response = $this->response->withHeader( 'Content-type', 'application/pdf' );
            break;
        case "odt":
            $response = $this->response->withHeader( 'Content-type', 'application/vnd.oasis.opendocument.text' );
            break;
        case "ott":
            $response = $this->response->withHeader( 'Content-type', 'application/vnd.oasis.opendocument.text-template' );
            break;
        case "docx":
            $response = $this->response->withHeader( 'Content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' );
            break;
        case "doc":
            $response = $this->response->withHeader( 'Content-type', 'application/msword' );
            break;
        case "txt":
            $response = $this->response->withHeader( 'Content-type', 'text/plain' );
            break;
        default:
            $response = $this->response->withHeader( 'Content-type', 'text/plain' );
    }
    $response->write($lob);
    return $response;
});

$app->get('/get-all-resumes', function (Request $request, Response $response, array $args) {
    $stmt = $this->get('db')->prepare("
         SELECT
            id,
            revision_count as revisionCount,
            last_modified AS latest,
            extension,
            filename,
            notes,
            original_basename AS originalBasename,
            purpose,
            created, 
            modified 
         FROM resumes
         WHERE updated_to IS NULL
         GROUP BY breadcrumb
         ORDER BY purpose, extension, modified DESC, breadcrumb;");
     $stmt->execute();
     $res = ResponseBody::fromResultSet($stmt->fetchAll());
     return $response->withJson($res());
 });
 