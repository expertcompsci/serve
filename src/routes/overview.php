<?php
use Slim\Http\Request;
use Slim\Http\Response;
use \Serve\Helpers\ResponseBody;
use \Serve\Helpers\AppErrors;
use \Serve\Exceptions\RowNotFoundException;

$app->get('/get-db-status', function (Request $request, Response $response, array $args) {
    $db = $this->get('db');
    //return print_r($db->getAttribute(PDO::ATTR_SERVER_INFO), TRUE);
    $stmt = $this->get('db')->prepare("
    SELECT 
        (SELECT CONCAT('version:[', `version`, '] notes:[', `notes`, '] date:[', `modified`, ']') 
        FROM `versions`
        ORDER BY `modified` DESC LIMIT 1) AS version,
        (SELECT database()) AS databaseName
    ");
    $stmt->execute();
    $res = ResponseBody::fromResultSet($stmt->fetchAll());
    return $response->withJson($res());
});


$app->get('/get-latest-submissions', function (Request $request, Response $response, array $args) {
    $stmt = $this->get('db')->prepare("
        SELECT 
            position, employers.company_name as companyName
        FROM 
            submissions, employers
        WHERE
            submissions.employer_id = employers.id
            AND
            TIMESTAMPDIFF(DAY, submissions.submitted_datetime, NOW()) < 5");
    $stmt->execute();
    $res = ResponseBody::fromResultSet($stmt->fetchAll());
    return $response->withJson($res());
});

$app->get('/get-overview', function (Request $request, Response $response, array $args) {
   $stmt = $this->get('db')->prepare(
    "SELECT
        (SELECT 
            count(id) 
        FROM 
            submissions 
        WHERE 
            TIMESTAMPDIFF(DAY, submitted_datetime, NOW()) < 5) AS submissionsLastFiveDays,
        (SELECT 
            count(id) 
        FROM 
            job_ads 
        WHERE 
            TIMESTAMPDIFF(DAY, created, NOW()) < 5) AS jobAdsPostedLastFiveDays,
        (SELECT 
            count(id) 
        FROM 
            resumes) AS resumeCount,
        (SELECT 
            created
        FROM 
            resumes
        ORDER BY
            created ASC
        LIMIT 1) AS latestResumeAddedDatetime,
        (SELECT 
            count(id) 
        FROM 
            letters) AS letterCount,
        (SELECT 
            created
        FROM 
            letters
        ORDER BY
            created ASC
        LIMIT 1) AS latestLetterAddedDatetime,
        (SELECT 
            count(id) 
        FROM 
            employers) AS employerCount,
        (SELECT 
            created
        FROM 
            employers
        ORDER BY
            created DESC
        LIMIT 1) AS latestEmployerAddedDatetime "
    );
    $stmt->execute();
    $res = ResponseBody::fromResultSet(Array($stmt->fetch()));
    return $response->withJson($res());
});

$app->get('/get-latest-events/{count}', function (Request $request, Response $response, array $args) {
    $sql = "SELECT 
            * 
        FROM
            event_log
        ORDER BY
            created DESC";
    if($args["count"] > 0) {
        $sql = $sql . " LIMIT :parameter_count";
        $stmt = $this->get('db')->prepare($sql);
        $stmt->bindValue(':parameter_count', intval($args['count']), PDO::PARAM_INT);
    } else {
        $stmt = $this->get('db')->prepare($sql);
    }
    $stmt->execute();
    $res = ResponseBody::fromResultSet($stmt->fetchAll());
    return $response->withJson($res());
});

$app->get('/get-latest-events-summary/{count}', function (Request $request, Response $response, array $args) {
    $sql ="SELECT 
            CASE
                WHEN employer_id IS NOT NULL THEN 'employer'
                WHEN job_ad_id IS NOT NULL THEN 'jobAd' 
                WHEN resume_id IS NOT NULL THEN 'resume'
                WHEN submission_id IS NOT NULL THEN 'submission'
                WHEN letter_id IS NOT NULL THEN 'letter' 
                END AS entity,
            standard_name AS standardName,
            operation,
            notes,
            event_datetime AS eventDateTime
        FROM
            event_log
        ORDER BY
            created DESC";
    if($args["count"] > 0) {
        $sql = $sql . " LIMIT :parameter_count";
        $stmt = $this->get('db')->prepare($sql);
        $stmt->bindValue(':parameter_count', intval($args['count']), PDO::PARAM_INT);
    } else {
        $stmt = $this->get('db')->prepare($sql);
    }
    $stmt->execute();
    $res = ResponseBody::fromResultSet($stmt->fetchAll());
    return $response->withJson($res());
});
 