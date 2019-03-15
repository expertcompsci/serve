<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Respect\Validation\Validator as v;
use \DavidePastore\Slim\Validation\Validation;
use \Serve\Helpers\ResponseBody;
use \Serve\Helpers\AppErrors;
use \Serve\Helpers\ModelBody;
use \Serve\Exceptions\RowNotInsertedException;
use \Serve\Exceptions\RowNotUpdatedException;
use \Serve\Exceptions\RowNotDeletedException;
use \Serve\Exceptions\RowNotFoundException;

$titleValidator = v::length(3, 80);
$adContentValidator = v::length(3, 60000);
$notesValidator = v::optional(v::length(3, 60000));
$postedDatetimeValidator = v::length(19);
$sourceUrlValidator = v::optional(v::url());
$sourceValidator = v::optional(v::length(3, 80));
$byEmailValidator = v::boolVal();
$employerCompanyNameValidator = v::alnum()->length(3, 80);
$idValidator = v::numeric()->positive();
$searchTitleValidator = v::optional(v::alnum()->length(0, 80));
$searchAdContentValidator = v::optional(v::length(0, 80));
$searchNotesValidator = v::optional(v::length(0, 80));

$validators = array(
    'title' => $titleValidator,
    'adContent' => $adContentValidator,
    'notes' => $notesValidator,
    'postedDatetime' => $postedDatetimeValidator,
    'sourceUrl' => $sourceUrlValidator,
    'source' => $sourceValidator,
    'byEmail' => $byEmailValidator
    );

class JobAd extends ModelBody {
    protected const initBody = [
        "title" => [":parameter_title", ""],
        "adContent" => [":parameter_adContent", ""],
        "notes" => [":parameter_notes", ""],
        "postedDatetime" => [":parameter_posted_datetime", ""],
        "sourceUrl" => [":parameter_source_url", ""],
        "source" => [":parameter_source", ""],
        "byEmail" => [":parameter_by_email", false, PDO::PARAM_BOOL],
        "employerId" => [":parameter_employerId", null, PDO::PARAM_INT]
    ];
}

$app->post('/insert-job-ad', function (Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = new JobAd($request->getParsedBody());
    $stmt = $this->get('db')->prepare("CALL proc_insert_job_ad(" . $body->emitSqlParamList() . ")");
    $body->bind($stmt);
    try {
        if($stmt->execute() === true){
            $res = ResponseBody::fromOkMessage('Inserted job ad.');
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
})->add(new Validation($validators));;

$app->post('/update-job-ad', function (Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = new JobAd($request->getParsedBody());
    $stmt = $this->get('db')->prepare(
        "CALL proc_update_job_ad(:parameter_id, " . $body->emitSqlParamList() . ")"
    );
    $body->bind($stmt);
    $stmt->bindValue(':parameter_id', $request->getParsedBody()['id'], PDO::PARAM_INT);
    try {
        if(($stmt->execute() === true)){
            $res = ResponseBody::fromOkMessage('Updated job ad.');
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

$app->delete('/delete-job-ad/{id}', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = $request->getParsedBody();
    $stmt = $this->get('db')->prepare("DELETE FROM job_ads WHERE id = :parameter_id");
    $stmt->bindValue(':parameter_id', $args['id'], PDO::PARAM_INT);
    if(($stmt->execute() === true) && ($stmt->rowCount() == 1)){
        $res = ResponseBody::fromOkMessage('Deleted job ad.');
        return $response->withJson($res(), 200);
    } else {
        if($stmt->errorInfo()[0] != "00000") {
            throw RowNotDeletedException::fromErrInfo($stmt->errorInfo(), $body['id']);
        }
        throw RowNotDeletedException::fromId($body['id']);
    }
})->add(new Validation(array('id' => $idValidator)));

$app->get('/search-job-ads', function (Request $request, Response $response, array $args) {
    $appErrors = null;
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
    }
    $title = $request->getQueryParam('title');
    $adContent = $request->getQueryParam('ad-content');
    $notes = $request->getQueryParam('notes');
    $conditions = [];
    $parameters = [];
    if (!empty($title)){
        $conditions[] = 'title LIKE ?';
        $parameters[] = '%'.$title."%";
    }
    if (!empty($adContent)){
        $conditions[] = 'ad_content LIKE ?';
        $parameters[] = '%'.$adContent."%";
    }
    if (!empty($notes)){
        $conditions[] = 'notes LIKE ?';
        $parameters[] = '%'.$notes."%";
    }
    if(empty($conditions)) {
        if(empty($appErrors)){
            $appErrors = AppErrors::fromCodeTarget(AppErrors::errNoSearchTarg, 'title, add_content, notes');
        } else {
            $appErrors->addCodeTarget(AppErrors::errNoSearchTarg, 'title, add_content, notes');
        }
    }
    if(!empty($appErrors)){
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $sql = "SELECT * FROM job_ads WHERE " . implode(" AND ", $conditions);
    $stmt = $this->get('db')->prepare($sql);        
    $stmt->execute($parameters);
    $rowSet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rowSet['rowCount'] = $stmt->rowCount();
    $res = ResponseBody::fromResultSet($rowSet, array('byEmail'));
    return $response->withJson($res());
})->add(new Validation(array('title' => $searchTitleValidator, 'ad-content' => $searchAdContentValidator, 'notes' => $searchNotesValidator)));

$app->get('/get-job-ad/{id}', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $stmt = $this->get('db')->prepare("
        SELECT 
            id, 
            title,
            notes,
            ad_content AS adContent, 
            posted_datetime AS postedDatetime, 
            source_url AS sourceUrl, 
            source AS source, 
            employer_id AS employerId, 
            by_email AS byEmail,
            created, 
            modified 
        FROM job_ads
        WHERE id = :parameter_id
        order by title, modified DESC"
        );
    $stmt->bindValue(':parameter_id', $args['id']);
    $stmt->execute();
    if($stmt->rowCount() != 1) {
        throw RowNotFoundException::fromId($args['id']);
    }
    $res = ResponseBody::fromResultSet($stmt->fetchAll(), array('byEmail'));
    return $response->withJson($res());
})->add(new Validation(array('id' => $idValidator)));

$app->get('/get-all-job-ads', function (Request $request, Response $response, array $args) {
   $stmt = $this->get('db')->prepare("
        SELECT 
            job_ads.id, 
            job_ads.title,
            employers.company_name AS companyName,
            job_ads.notes,
            (SELECT COUNT(*) FROM submissions WHERE job_ads.id = submissions.job_ad_id) AS submissionCount,  
            job_ads.ad_content AS adContent, 
            job_ads.posted_datetime AS postedDatetime, 
            job_ads.source_url AS sourceUrl, 
            job_ads.source AS source, 
            job_ads.employer_id AS employerId, 
            job_ads.by_email AS byEmail,
            job_ads.created, 
            job_ads.modified 
        FROM job_ads, employers
        WHERE job_ads.employer_id = employers.id
        ORDER BY posted_datetime DESC
        ");
    $stmt->execute();
    $res = ResponseBody::fromResultSet($stmt->fetchAll(), array('byEmail'));
    return $response->withJson($res());
});
