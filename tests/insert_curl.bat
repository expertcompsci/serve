curl --request POST ^
  --url 'http://localhost:4200/insert-job-ad'  ^
  --header 'Content-Type: application/json'  ^
  --data '{ ^
    "title": "Inserted By Curl", ^
    "adContent": "This is ad content. It can be \r rather lengthy and \rcan contain control chars.", ^
    "notes": "This is \rthe  notes content. It can be \r rather lengthy and \rcan contain control chars.", ^
    "postedDatetime": "2018-10-30 10:11:12", ^
    "sourceUrl": "http://now.is.the/time", ^
    "byEmail": "Y",
