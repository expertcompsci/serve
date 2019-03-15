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

$descriptionValidator = v::length(0, 80);
$notesValidator = v::optional(v::length(1, 60000));

$searchDescriptionValidator = v::optional(v::length(0, 80));
$searchNotesValidator =  v::optional(v::length(0, 80));
$idValidator = v::numeric()->positive();

$validators = array(
    'description' => $descriptionValidator,
    'notes' => $notesValidator
);

$app->post('/upload-letter', function(Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $directory = $this->get('settings')['uploadDirectory'];
    $db = $this->get('db');
    $uploadedFiles = $request->getUploadedFiles();
    $uploadedFile = $uploadedFiles['letter'];   // Key is input control name attr
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $originalBasename = pathinfo($uploadedFile->getClientFilename(), PATHINFO_BASENAME);
        $basename = $originalBasename.'_'.bin2hex(random_bytes(8)).'_letter_';
        $filename = $basename.$extension;
        $filePath = $directory . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->moveTo($filePath);

        $blob = fopen($filePath, 'rb');
        $body = $request->getParsedBody();
        if(!empty($body['id'])) {
            $procName = "proc_commit_letter( :parameter_id,";
        } else {
            $procName = "proc_upload_letter(";
        }
        $sql = "CALL " . $procName . "
            :parameter_file_content, 
            :parameter_filename, 
            :parameter_original_basename, 
            :parameter_extension, 
            :parameter_description,
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
        $stmt->bindValue(':parameter_description', $body['description']);
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
                $res = ResponseBody::fromOkMessage('Uploaded letter.');
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

$app->post('/update-letter', function (Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = $request->getParsedBody();
    $stmt = $this->get('db')->prepare(
        "CALL proc_update_letter(
            :parameter_id,
            :parameter_description, 
            :parameter_notes)"
    );
    $stmt->bindValue(':parameter_description', $body['description']);
    $stmt->bindValue(':parameter_notes', $body['notes']);
    $stmt->bindValue(':parameter_id', $body['id'], PDO::PARAM_INT);
    try {
            if(($stmt->execute() === true)){
            $res = ResponseBody::fromOkMessage('Updated letter.');
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
})->add(new Validation(array('id' => $idValidator)));;

$app->get('/search-letters', function (Request $request, Response $response, array $args) {
    $appErrors = null;
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
    }
    $description = $request->getQueryParam('description');
    $notes = $request->getQueryParam('notes');
    $conditions = [];
    $parameters = [];
    if (!empty($description)){
        $conditions[] = 'description LIKE ?';
        $parameters[] = '%'.$description."%";
    }
    if (!empty($notes)){
        $conditions[] = 'notes LIKE ?';
        $parameters[] = '%'.$notes."%";
    }
    if(empty($conditions)) {
        if(empty($appErrors)){
            $appErrors = AppErrors::fromCodeTarget(AppErrors::errNoSearchTarg, 'description, notes');
        } else {
            $appErrors->addCodeTarget(AppErrors::errNoSearchTarg, 'description, notes');
        }
    }
    if(!empty($appErrors)){
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $sql = "SELECT * FROM letters WHERE " . implode(" AND ", $conditions);
    $stmt = $this->get('db')->prepare($sql);        
    $stmt->execute($parameters);
    $rowSet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rowSet['rowCount'] = $stmt->rowCount();
    $res = ResponseBody::fromResultSet($rowSet);
    return $response->withJson($res());
})->add(new Validation(array('description' => $searchDescriptionValidator, 'notes' => $searchNotesValidator)));

// Return letter meta-data, not the file or content
$app->get('/get-letter/{id}', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    // May be appropriate to include an existance check on associated file before returning
    $stmt = $this->get('db')->prepare("
        SELECT 
            id, 
            description,
            original_basename AS originalBasename,
            content_type AS contentType,
            last_modified AS lastModified,
            size,
            notes,
            created, 
            modified
        FROM letters
        WHERE id = :parameter_id");
    $stmt->bindValue(':parameter_id', $args['id']);
    $stmt->execute();
    if($stmt->rowCount() != 1) {
        throw RowNotFoundException::fromId($args['id']);
    }
    $res = ResponseBody::fromResultSet($stmt->fetchAll());
    return $response->withJson($res());
})->add(new Validation(array('id' => $idValidator)));

$app->delete('/delete-letter/{id}', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    // May be appropriate to include an existance check on associated file before returning
    $stmt = $this->get('db')->prepare("CALL proc_delete_all_letter_revisions(:parameter_id);");
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

$app->get('/view-letter/{id}', function($request, Slim\Http\Response $response, $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $stmt = $this->get('db')->prepare("
        SELECT file_content, letters.extension
        FROM letters, files
        WHERE letters.id = ? AND files.id = letters.files_id;");
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

$app->get('/get-all-letters', function (Request $request, Response $response, array $args) {
    $stmt = $this->get('db')->prepare("
         SELECT
            id,
            revision_count as revisionCount,
            last_modified AS latest,
            extension,
            filename,
            notes,
            original_basename,
            description,
            created, 
            modified 
         FROM letters
         WHERE updated_to IS NULL
         GROUP BY breadcrumb
         ORDER BY description, extension, modified DESC, breadcrumb;");
     $stmt->execute();
     $res = ResponseBody::fromResultSet($stmt->fetchAll());
     return $response->withJson($res());
 });
 