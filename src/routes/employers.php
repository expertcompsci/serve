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

$companyNameValidator = v::length(1, 80);
$isPrincipleValidator = v::boolVal();
$employesITValidator = v::boolVal();
$notesValidator = v::optional(v::length(1, 60000));
$siteUrlValidator = v::optional(v::url());
$idValidator = v::numeric()->positive();

$searchCompanyNameValidator = v::optional(v::alnum()->length(0, 80));
$searchNotesValidator =  v::optional(v::length(0, 80));

$validators = array(
    'companyName' => $companyNameValidator,
    'isPrinciple' => $isPrincipleValidator,
    'notes' => $notesValidator,
    'siteUrl' => $siteUrlValidator,
    'employsIt' => $employesITValidator
    );

class Employer extends ModelBody {
    protected const initBody = [
        "companyName" => [":parameter_company_name", ""],
        "isPrinciple" => [":parameter_is_principle", false, PDO::PARAM_BOOL],
        "notes" => [":parameter_notes",""],
        "employsIt" => [":parameter_employs_it", false, PDO::PARAM_BOOL],
        "siteUrl" => [":parameter_site_url",""]
    ];
}

$app->post('/insert-employer', function (Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = new Employer($request->getParsedBody());
    $stmt = $this->get('db')->prepare(
        "CALL proc_insert_employer(" . $body->emitSqlParamList() . ")"
    );
    $body->bind($stmt);
    try{
        if($stmt->execute() === true){
            $res = ResponseBody::fromOkMessage('Inserted employer.');
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
})->add(new Validation($validators));

$app->post('/update-employer', function (Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = new Employer($request->getParsedBody());
    $stmt = $this->get('db')->prepare("CALL proc_update_employer(" . $body->emitSqlParamList() . ")");
    $body->bind($stmt);
    $stmt->bindValue(':parameter_id', $body->getId(), PDO::PARAM_INT);
    try {
        if(($stmt->execute() === true)){
            $res = ResponseBody::fromOkMessage('Updated employer.');
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
})->add(new Validation($validators));

$app->delete('/delete-employer/{id}', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = $request->getParsedBody();
    $stmt = $this->get('db')->prepare("DELETE FROM employers WHERE id = :parameter_id");
    $stmt->bindValue(':parameter_id', $args['id'], PDO::PARAM_INT);
    if(($stmt->execute() === true) && ($stmt->rowCount() == 1)){
        $res = ResponseBody::fromOkMessage('Deleted employer.');
        return $response->withJson($res(), 200);
    } else {
        if($stmt->errorInfo()[0] != "00000") {
            throw RowNotDeletedException::fromErrInfo($stmt->errorInfo(), $body['id']);
        }
        throw RowNotDeletedException::fromId($body['id']);
    }
})->add(new Validation(array('id' => $idValidator)));

$app->get('/search-employers', function (Request $request, Response $response, array $args) {
    $appErrors = null;
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
    }
    $companyName = $request->getQueryParam('company-name');
    $notes = $request->getQueryParam('notes');
    $conditions = [];
    $parameters = [];
    if (!empty($companyName)){
        $conditions[] = 'company_name LIKE ?';
        $parameters[] = '%'.$companyName."%";
    }
    if (!empty($notes)){
        $conditions[] = 'notes LIKE ?';
        $parameters[] = '%'.$notes."%";
    }
    if(empty($conditions)) {
        if(empty($appErrors)){
            $appErrors = AppErrors::fromCodeTarget(AppErrors::errNoSearchTarg, 'company-name, notes');
        } else {
            $appErrors->addCodeTarget(AppErrors::errNoSearchTarg, 'title, add_content, notes');
        }
    }
    if(!empty($appErrors)){
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $sql = "SELECT 
                id, 
                company_name AS companyName,
                is_principle AS isPrinciple,
                notes,
                employs_it AS employsIt, 
                site_url AS siteUrl, 
                created, 
                modified 
            FROM employers WHERE " . implode(" AND ", $conditions);
    $stmt = $this->get('db')->prepare($sql);        
    $stmt->execute($parameters);
    $rowSet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rowSet['rowCount'] = $stmt->rowCount();
    $res = ResponseBody::fromResultSet($rowSet, array("isPrinciple", "employsIt"));
    return $response->withJson($res());
})->add(new Validation(array('company-name' => $searchCompanyNameValidator, 'notes' => $searchNotesValidator)));

$app->get('/get-employer/{id}', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $stmt = $this->get('db')->prepare("
        SELECT 
            id, 
            company_name AS companyName,
            is_principle AS isPrinciple,
            notes,
            employs_it AS employsIt, 
            site_url AS siteUrl, 
            created, 
            modified 
        FROM employers
        WHERE id = :parameter_id
        ORDER by company_name, modified DESC"
        );
    $stmt->bindValue(':parameter_id', $args['id']);
    $stmt->execute();
    if($stmt->rowCount() != 1) {
        throw RowNotFoundException::fromId($args['id']);
    }
    $res = ResponseBody::fromResultSet($stmt->fetchAll(), 
        array("isPrinciple", "employsIt"));     // Names of booleans to convert from int to json true/false
    return $response->withJson($res());
})->add(new Validation(array('id' => $idValidator)));

$app->get('/get-all-employers', function (Request $request, Response $response, array $args) {
   $stmt = $this->get('db')->prepare("
        SELECT 
            id, 
            company_name AS companyName,
            (SELECT MAX(posted_datetime) FROM job_ads WHERE job_ads.employer_id = employers.id GROUP BY employer_id) AS latestJobAd,
            is_principle AS isPrinciple,
            notes,
            employs_it AS employsIt, 
            site_url AS siteUrl, 
            created, 
            modified 
        FROM employers
        ORDER BY latestJobAd DESC
        ");
    $stmt->execute();
    $res = ResponseBody::fromResultSet($stmt->fetchAll(), array("isPrinciple", "employsIt"));
    return $response->withJson($res());
});
