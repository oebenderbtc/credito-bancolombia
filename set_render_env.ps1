$headers = @{
  "Authorization" = "Bearer rnd_c2xNrNzUWjdEm5hrIYvF01ezKNjs"
  "Accept"        = "application/json"
  "Content-Type"  = "application/json"
}
$svcId = "srv-da2cre1t0dsc738uvbog"

Write-Host "=== PATCH env vars (bulk) ==="
$body = @{
  envVars = @(
    @{ key = "DB_HOST"; value = "2.25.79.121" },
    @{ key = "DB_PORT"; value = "3306" },
    @{ key = "DB_NAME"; value = "u423799403_eldemon777" },
    @{ key = "DB_USER"; value = "u423799403_eldemon777" },
    @{ key = "DB_PASS"; value = "777Eldemon" }
  )
} | ConvertTo-Json -Depth 4

Write-Host $body

try {
  $r = Invoke-RestMethod -Method PATCH -Uri "https://api.render.com/v1/services/$svcId/env-vars" -Headers $headers -Body $body -ErrorAction Stop
  $r | ConvertTo-Json -Depth 8
  exit 0
} catch {
  Write-Host "PATCH ERROR: $($_.Exception.Message)"
  if ($_.Exception.Response) {
    try { $resp = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream()).ReadToEnd(); Write-Host "RESP BODY: $resp" } catch {}
  }
}

Write-Host ""
Write-Host "=== Fallback: POST 1 by 1 env var ==="
@(
  @{k="DB_HOST"; v="2.25.79.121"},
  @{k="DB_PORT"; v="3306"},
  @{k="DB_NAME"; v="u423799403_eldemon777"},
  @{k="DB_USER"; v="u423799403_eldemon777"},
  @{k="DB_PASS"; v="777Eldemon"}
) | ForEach-Object {
  $b = @{ key = $_.k; value = $_.v } | ConvertTo-Json
  Write-Host "POST $($_.k)"
  try {
    $rr = Invoke-RestMethod -Method POST -Uri "https://api.render.com/v1/services/$svcId/env-vars" -Headers $headers -Body $b -ErrorAction Stop
    Write-Host "OK: $($rr | ConvertTo-Json -Compress)"
  } catch {
    Write-Host "POST $($_.k) ERROR: $($_.Exception.Message)"
    if ($_.Exception.Response) {
      try { $resp = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream()).ReadToEnd(); Write-Host "  RESP: $resp" } catch {}
    }
  }
}

exit 0
