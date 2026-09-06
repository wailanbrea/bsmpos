[CmdletBinding(SupportsShouldProcess = $true)]
param()

$serviceExe = Join-Path $PSScriptRoot "OmniPOSAgent.exe"
if (-not (Test-Path $serviceExe)) {
    Write-Error "No se encontró el ejecutable del servicio: $serviceExe"
    return
}

Write-Host "Instalando servicio OmniPOS Windows Agent..." -ForegroundColor Cyan
& $serviceExe install
& $serviceExe start
Write-Host "Servicio instalado e iniciado." -ForegroundColor Green
