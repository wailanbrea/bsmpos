[CmdletBinding(SupportsShouldProcess = $true)]
param()

$serviceExe = Join-Path $PSScriptRoot "OmniPOSAgent.exe"
if (-not (Test-Path $serviceExe)) {
    Write-Error "No se encontró el ejecutable del servicio: $serviceExe"
    return
}

Write-Host "Deteniendo y desinstalando servicio OmniPOS Windows Agent..." -ForegroundColor Yellow
& $serviceExe stop
& $serviceExe uninstall
Write-Host "Servicio desinstalado." -ForegroundColor Green
