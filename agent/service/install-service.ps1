[CmdletBinding(SupportsShouldProcess = $true)]
param()

$serviceExe = Join-Path $PSScriptRoot "BSMPOSAgent.exe"
if (-not (Test-Path $serviceExe)) {
    Write-Error "No se encontró el ejecutable del servicio: $serviceExe"
    return
}

Write-Host "Instalando servicio BSM-POS Windows Agent..." -ForegroundColor Cyan
& $serviceExe install
& $serviceExe start
Write-Host "Servicio instalado e iniciado." -ForegroundColor Green