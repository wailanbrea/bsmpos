[CmdletBinding()]
param(
    [Parameter(Mandatory = $false)]
    [string] $ProjectRoot = '',

    [Parameter(Mandatory = $false)]
    [string] $BackendHost = '127.0.0.1',

    [Parameter(Mandatory = $false)]
    [ValidateRange(1, 65535)]
    [int] $BackendPort = 8001,

    [Parameter(Mandatory = $false)]
    [ValidateRange(1, 65535)]
    [int] $AgentPort = 8765,

    [Parameter(Mandatory = $false)]
    [switch] $SkipElectron
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
    $ProjectRoot = Split-Path -Parent $PSScriptRoot
}
$ProjectRoot = [System.IO.Path]::GetFullPath($ProjectRoot)
if (-not (Test-Path -LiteralPath $ProjectRoot -PathType Container)) {
    throw "No existe la raíz del proyecto '$ProjectRoot'."
}

function Test-HttpEndpoint {
    param([Parameter(Mandatory = $true)][string] $Url)

    try {
        $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 3 -Proxy $null
        return ($response.StatusCode -eq 200)
    } catch {
        return $false
    }
}

function Wait-HttpEndpoint {
    param(
        [Parameter(Mandatory = $true)][string] $Url,
        [Parameter(Mandatory = $true)][string] $Name,
        [int] $TimeoutSeconds = 30
    )

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    do {
        if (Test-HttpEndpoint -Url $Url) {
            Write-Host "$Name disponible: $Url" -ForegroundColor Green
            return
        }
        Start-Sleep -Milliseconds 500
    } while ((Get-Date) -lt $deadline)

    throw "$Name no respondió dentro de $TimeoutSeconds segundos: $Url"
}

$phpPath = 'C:\xampp\php\php.exe'
if (-not (Test-Path -LiteralPath $phpPath -PathType Leaf)) {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($null -eq $phpCommand) {
        throw 'No se encontró PHP. Instala PHP o ajusta la ruta del lanzador.'
    }
    $phpPath = $phpCommand.Source
}

$agentRoot = Join-Path $ProjectRoot 'agent\build\install\omnipos-windows-agent'
$agentLauncher = Join-Path $agentRoot 'bin\omnipos-windows-agent.bat'
$logsRoot = Join-Path $ProjectRoot 'storage\logs'
New-Item -ItemType Directory -Path $logsRoot -Force | Out-Null

$backendUrl = "http://${BackendHost}:${BackendPort}"
$agentUrl = "http://127.0.0.1:${AgentPort}/api/status"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  OmniPOS Modular SaaS - Suite Windows    " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# 1. Comprobar / Iniciar Laravel
if (Test-HttpEndpoint -Url $backendUrl) {
    Write-Host "Laravel ya está activo en $backendUrl." -ForegroundColor Green
} else {
    Write-Host "Iniciando Laravel en $backendUrl..." -ForegroundColor Yellow
    $backendLog = Join-Path $logsRoot 'laravel-serve.log'
    Start-Process -FilePath $phpPath `
        -ArgumentList @('artisan', 'serve', "--host=$BackendHost", "--port=$BackendPort") `
        -WorkingDirectory $ProjectRoot `
        -RedirectStandardOutput $backendLog `
        -RedirectStandardError $backendLog `
        -WindowStyle Hidden
    Wait-HttpEndpoint -Url $backendUrl -Name 'Laravel'
}

# 2. Comprobar / Iniciar OmniPOS Windows Agent
if (Test-HttpEndpoint -Url $agentUrl) {
    Write-Host "OmniPOS Windows Agent ya está activo en $agentUrl." -ForegroundColor Green
} else {
    if (-not (Test-Path -LiteralPath $agentLauncher -PathType Leaf)) {
        Write-Host "No se encontró el ejecutable empaquetado en '$agentLauncher'. Compilando con Gradle..." -ForegroundColor Yellow
        $gradleCmd = "C:\xampp\php\www\BSRentCar\tools\gradle-9.6.1\gradle-9.6.1\bin\gradle.bat"
        if (Test-Path $gradleCmd) {
            & $gradleCmd -p (Join-Path $ProjectRoot 'agent') installDist
        } else {
            throw "No se encontró Gradle ni el paquete del agente. Ejecuta 'gradle installDist' en agent/."
        }
    }

    Write-Host "Iniciando OmniPOS Windows Agent en $agentUrl..." -ForegroundColor Yellow
    $agentLog = Join-Path $logsRoot 'agent-runner.log'
    Start-Process -FilePath $agentLauncher `
        -WorkingDirectory (Join-Path $ProjectRoot 'agent') `
        -RedirectStandardOutput $agentLog `
        -RedirectStandardError $agentLog `
        -WindowStyle Hidden
    Wait-HttpEndpoint -Url $agentUrl -Name 'OmniPOS Windows Agent'
}

# 3. Lanzar Electron o Finalizar
if ($SkipElectron) {
    Write-Host "Suite iniciada en modo navegador web: $backendUrl" -ForegroundColor Green
    return
}

Write-Host "Iniciando contenedor Electron..." -ForegroundColor Yellow
$npxCmd = Get-Command npx.cmd -ErrorAction SilentlyContinue
if ($null -ne $npxCmd) {
    $env:OMNIPOS_URL = $backendUrl
    Start-Process -FilePath $npxCmd.Source `
        -ArgumentList @('electron', '.') `
        -WorkingDirectory $ProjectRoot `
        -NoNewWindow
    Write-Host "Contenedor Electron iniciado correctamente." -ForegroundColor Green
} else {
    Write-Warning "No se encontró npx para arrancar Electron. Abriendo navegador predeterminado..."
    Start-Process $backendUrl
}
