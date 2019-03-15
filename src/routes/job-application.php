<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Respect\Validation\Validator as v;
use \DavidePastore\Slim\Validation\Validation;
use \Serve\Helpers\ResponseBody;
use \Serve\Helpers\AppErrors;
use \Serve\Helpers\ModelBody;
use \Serve\Exceptions\RowNotInsertedException;

$companyNameValidator = v::length(1, 80);
$titleValidator = v::length(3, 80);
$sourceUrlValidator = v::optional(v::url());
$sourceValidator = v::optional(v::length(3, 80));
$adContentValidator = v::length(3, 60000);

$validators = array(
    'companyName' => $companyNameValidator,
    'title' => $titleValidator,
    'sourceUrl' => $sourceUrlValidator,
    'source' => $sourceValidator,
    'adContent' => $adContentValidator,
    );

class Application extends ModelBody {
    protected const initBody = [
        "companyName" => [":parameter_company_name", ""],
        "title" => [":parameter_title", ""],
        "adContent" => [":parameter_ad_content", ""],
        "resumeId" => [":parameter_resumeId", null, PDO::PARAM_INT],
        "source" => [":parameter_source", ""],
        "sourceUrl" => [":parameter_source_url", ""]
    ];
}

$app->post('/insert-application', function (Request $request, Response $response) {
    if($request->getAttribute('has_errors')){
        $appErrors = AppErrors::fromValidation($request->getAttribute('errors'));
        $res = ResponseBody::fromAppErrors($appErrors);
        return $response->withJson($res(), 200);
    }
    $body = new Application($request->getParsedBody());
    $stmt = $this->get('db')->prepare("CALL proc_application(" . $body->emitSqlParamList() . ")");
    $body->bind($stmt);
    try {
        if($stmt->execute() === true){
            $res = ResponseBody::fromOkMessage('Inserted application.');
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
