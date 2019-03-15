


$headers = New-Object "System.Collections.Generic.Dictionary[[String],[String]]"
$headers.Add("Content-type", 'application/json')

$bodyStr = @{
    title = "Inserted By PowerShell"
    adContent= "This is ad content."
    notes= "This is \rthe  notes content."
    postedDatetime= "2018-10-30 10:11:12"
    sourceUrl= "http://now.is.the/time"
    byEmail= "Y"
    employerCompanyName= "Super Fine Employer"
}
$json = $bodyStr | ConvertTo-Json
$response = Invoke-RestMethod 'http://psalte.com/Ben/public/insert-job-ad' `
        -Method POST `
        -Body $json `
        -Headers $headers
echo $response