![Job Searcher](JobSearcher.png)

# Serve - The Job $earcher Back-End

Serve is a RESTful back-end for a database driven application, called **Job $earcher**, to help document and organize an individual's search for a job. The purpose is to demonstrate a fully developed, tested and deployed RESTful back-end. For an example of a fully decoupled front end see the repository: [frontice](https://github.com/expertcompsci/frontice).

## Back-end Languages, Libraries, Technologies

* PHP 5.5.0 or greater
* Server side data validation
* Slim 3 REST framework
* JSON response API standard
* Multi-part form upload
* MariaDB version 15.1
* SQL blob types storage/retrieval

## Security

Because the purpose is to exemplify and demonstrate an application, there are no assumptions about the web server. Therefore authorization is not part of this demonstration app - but easily could be. For example if the app were served via TLS, which would be required to prevent man-in-the-middle attacks, OAuth 2 could be easily added using [OAuth 2.0 Server](https://oauth2.thephpleague.com/). Of course, server independent standards such as CORs headers, data validation, and variable binding are used as expected.

## Install the Application

* Point your virtual host document root to your new `serve/public/` directory.
* Ensure folders `logs/` and `uploads/` exist and are web writeable.

To run the application in development, if you install [Composer](https://getcomposer.org/) you can run these commands 

```bash
  cd serve
  php composer.phar start
```

## Testing

Testing is left to REST interface test tools like curl or Postman.

**Comming soon**, entire test suite in Postman generated PHP for curl. For example:

```PHP
<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_PORT => "8080",
  CURLOPT_URL => "http://localhost:8080/upload-resume",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"purpose\"\r\n\r\nTbP696m aMR46Za 7zCKajC 7rNYJ B2UX\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"notes\"\r\n\r\nHere are some notes about this resume\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"lastModifiedDateTime\"\r\n\r\n2018-05-05 10:10:10\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
  CURLOPT_HTTPHEADER => array(
    "Postman-Token: 35400d28-0f05-403a-9638-038a86fee0e6",
    "cache-control: no-cache",
    "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}
```
