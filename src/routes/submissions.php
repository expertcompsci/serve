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

$positionValidator = v::length(1, 80);
$isRejectedValidator = v::optional(v::boolVal());
$contactPositionValidator = v::optional(v::length(1, 80));
$contactFirstValidator = v::optional(v::length(1, 80));
$contactLastValidator = v::optional(v::length(1, 80));
$contactInitialValidator = v::optional(v::length(1, 80));
$contactPrefixValidator = v::optional(v::length(1, 80));
$contactEmailValidator = v::optional(v::length(1, 80));
$contactPhoneValidator = v::optional(v::length(1, 80));
$contactSiteValidator = v::optional(v::url());
$submittedDatetimeValidator = v::optional(v::length(19));
$notesValidator = v::optional(v::length(1, 60000));
$idValidator = v::numeric()->positive();

$searchTitleValidator = v::optional(v::alnum()->length(0, 80));
$searchAdContentValidator = v::optional(v::length(0, 80));
$searchNotesValidator = v::optional(v::length(0, 80));

$validators = array(
    "position" => $positionValidator,
    "isRejected" => $isRejectedValidator,
    "contactPosition" => $contactPositionValidator,
    "contactFirst" => $contactFirstValidator,
    "contactLast" => $contactLastValidator,
    "contactInitial" => $contactInitialValidator,
    "contactPrefix" => $contactPrefixValidator,
    "contactEmail" => $contactEmailValidator,
    "contactPhone" => $contactPhoneValidator,
    "contactSite" => $contactSiteValidator,
    "submittedDatetime" => $submittedDatetimeValidator,
    "notes" => $notesValidator
);

class Submission extends ModelBody {
    protected const initBody = [
        "position" => [":parameter_position", ""],
        "isRejected" => [":parameter_is_rejected", false, PDO::PARAM_BOOL],
        "employerId" => [":parameter_employer_id", null, PDO::PARAM_INT],
        "jobAdId" => [":parameter_job_ad_id", null, PDO::PARAM_INT],
        "resumeId" => [":parameter_resume_id", null, PDO::PARAM_INT],
        "letterId" => [":parameter_letter_id", null, PDO::PARAM_INT],
        "notes" => [":parameter_notes", ""],
        "submittedDatetime" => [":parameter_submitted_datetime", ""],
        "contactPosition" => [":parameter_contact_position", ""],
        "contactFirst" => [":parameter_contact_first", ""],
        "contactLast" => [":parameter_contact_last", ""],
        "contactInitial" => [":parameter_contact_initial", ""],
        "contactPrefix" => [":parameter_contact_prefix", ""],
        "contactEmail" => [":parameter_contact_email", ""],
        "contactPhone" => [":parameter_contact_phone", ""],
        "contactSite" => [":parameter_contact_site", ""]
    ];
}

$app->post('/insert-submission', function (Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = new Submission($request->getParsedBody());
    $stmt = $this->get('db')->prepare("CALL proc_insert_submission(". $body->emitSqlParamList() . ")");
    $body->bind($stmt);
    if($stmt->execute() === true){
        $res = ResponseBody::fromOkMessage('Inserted submission.');
        return $response->withJson($res(), 201);
    } else {
        if($stmt->errorInfo()[0] != "00000") {
            throw RowNotInsertedException::fromErrInfo($stmt->errorInfo());
        }
        throw RowNotInsertedException::fromRowCount($stmt->rowCount());
    }
})->add(new Validation($validators));;

$app->post('/update-submission', function (Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = new Submission($request->getParsedBody());
    $stmt = $this->get('db')->prepare("CALL proc_update_submission(" . $body->emitSqlParamList() . ")");
    $body->bind($stmt);
    $stmt->bindValue(':parameter_id', $body->getId(), PDO::PARAM_INT);
    try {
        if(($stmt->execute() === true)){
            $res = ResponseBody::fromOkMessage('Updated Submission.');
            return $response->withJson($res(), 200);
        } else {
            if($stmt->errorInfo()[0] != "00000") {
                throw RowNotUpdatedException::fromErrInfo($stmt->errorInfo(), $body->getId());
            }
            throw RowNotUpdatedException::fromId($body->getId());
        }
    } catch (Exception $e) {
        $res = ResponseBody::fromException($e);
        return $response->withJson($res(), 200);
    }
})->add(new Validation(array('id' => $idValidator)));;

$app->delete('/delete-submission/{id}', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = $request->getParsedBody();
    $stmt = $this->get('db')->prepare("DELETE FROM submissions WHERE id = :parameter_id");
    $stmt->bindValue(':parameter_id', $args['id'], PDO::PARAM_INT);
    if(($stmt->execute() === true) && ($stmt->rowCount() == 1)){
        $res = ResponseBody::fromOkMessage('Deleted submission.');
        return $response->withJson($res(), 200);
    } else {
        if($stmt->errorInfo()[0] != "00000") {
            throw RowNotDeletedException::fromErrInfo($stmt->errorInfo(), $body['id']);
        }
        throw RowNotDeletedException::fromId($body['id']);
    }
})->add(new Validation(array('id' => $idValidator)));

$app->get('/search-submissions', function (Request $request, Response $response, array $args) {
    $appErrors = null;
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
    }
    $title = $request->getQueryParam('position');
    $notes = $request->getQueryParam('notes');
    $conditions = [];
    $parameters = [];
    if (!empty($title)){
        $conditions[] = 'position LIKE ?';
        $parameters[] = '%'.$title."%";
    }
    if (!empty($notes)){
        $conditions[] = 'notes LIKE ?';
        $parameters[] = '%'.$notes."%";
    }
    if(empty($conditions)) {
        if(empty($appErrors)){
            $appErrors = AppErrors::fromCodeTarget(AppErrors::errNoSearchTarg, 'position, notes');
        } else {
            $appErrors->addCodeTarget(AppErrors::errNoSearchTarg, 'position, notes');
        }
    }
    if(!empty($appErrors)){
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $sql = "SELECT * FROM submissions WHERE " . implode(" AND ", $conditions);
    $stmt = $this->get('db')->prepare($sql);        
    $stmt->execute($parameters);
    $rowSet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rowSet['rowCount'] = $stmt->rowCount();
    $res = ResponseBody::fromResultSet($rowSet, array("isRejected"));
    return $response->withJson($res());
})->add(new Validation(array('position' => $positionValidator, 'notes' => $searchNotesValidator)));

$app->get('/get-submission/{id}', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $stmt = $this->get('db')->prepare("
    SELECT 
        `submissions`.id,
        `position`,
        `is_rejected` AS isRejected,
        `job_ad_id` AS jobAdId,
        `submissions`.`employer_id` AS employerId,
        `resume_id` AS resumeId,
        `letter_id` AS letterId,
        `contact_position` AS contactPosition,
        `contact_first` AS contactFirst,
        `contact_last` AS contactLast,
        `contact_initial` AS contactInitial,
        `contact_prefix` AS contactPrefix,
        `contact_email` AS contactEmail,
        `contact_phone` AS contactPhone,
        `contact_site` AS contactSite,
        `submissions`.`notes`,
        `submitted_datetime` AS submittedDatetime
    FROM submissions
    WHERE submissions.id = :parameter_id
    order by `submissions`.modified"
        );
    $stmt->bindValue(':parameter_id', $args['id']);
    $stmt->execute();
    if($stmt->rowCount() != 1) {
        throw RowNotFoundException::fromId($args['id']);
    }
    $res = ResponseBody::fromResultSet($stmt->fetchAll(), array("isRejected"));
    return $response->withJson($res());
})->add(new Validation(array('id' => $idValidator)));

$app->get('/get-all-submissions', function (Request $request, Response $response, array $args) {
   $stmt = $this->get('db')->prepare("
        SELECT 
        submissions.`id`,
		submissions.`position`,
		submissions.`is_rejected` AS isRejected,
        submissions.`job_ad_id` AS jobAdId,
        submissions.`employer_id` AS employerId,
        submissions.`resume_id` AS resumeId,
        employers.`company_name` AS companyName,
		submissions.`contact_position` AS contactPosition,
		submissions.`contact_first` AS contactFirst,
		submissions.`contact_last` AS contactLast,
		submissions.`contact_initial` AS contactInitial,
		submissions.`contact_prefix` AS contactPrefix,
		submissions.`contact_email` AS contactEmail,
		submissions.`contact_phone` AS contactPhone,
		submissions.`contact_site` AS contactSite,
		submissions.`notes`,
		submissions.`submitted_datetime` AS submittedDatetime
        FROM submissions, employers
        WHERE submissions.employer_id = employers.id
        ORDER BY submitted_datetime DESC"
    );
    $stmt->execute();
    $res = ResponseBody::fromResultSet($stmt->fetchAll(), array("isRejected"));
    return $response->withJson($res());
});
