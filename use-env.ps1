$ProjectRoot = $PSScriptRoot
$PhpDir = Join-Path $ProjectRoot '.runtime\php'
$PhpBinary = Join-Path $PhpDir 'php.exe'
$ComposerPhar = Join-Path $ProjectRoot '.runtime\composer\composer.phar'
$NodeCandidates = @(
    (Join-Path $ProjectRoot '.runtime\node'),
    (Join-Path $ProjectRoot '.runtime\nodejs')
)

if (-not (Test-Path $PhpBinary)) {
    Write-Error "Local PHP runtime not found at $PhpDir"
    exit 1
}

if (-not (Test-Path $ComposerPhar)) {
    Write-Error "Local Composer runtime not found at $ComposerPhar"
    exit 1
}

$PhpVersion = & $PhpBinary -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;"

if ($LASTEXITCODE -ne 0) {
    Write-Error "Unable to determine the project PHP runtime version from $PhpBinary"
    exit 1
}

if ($PhpVersion -ne '8.4') {
    Write-Error "NativePHP in this project is pinned to PHP 8.4. The local runtime at $PhpBinary reports PHP $PhpVersion, but the installed nativephp/php-bin package only provides Windows runtimes for PHP 8.3 and 8.4."
    Write-Host "Replace .runtime\\php with a Windows x64 PHP 8.4 NTS build, then re-run .\\use-env.ps1."
    exit 1
}

$NodeDir = $null

foreach ($Candidate in $NodeCandidates) {
    if (Test-Path (Join-Path $Candidate 'node.exe')) {
        $NodeDir = $Candidate
        break
    }

    if (-not (Test-Path $Candidate)) {
        continue
    }

    $NestedNodeDir = Get-ChildItem $Candidate -Directory -ErrorAction SilentlyContinue |
        Where-Object { Test-Path (Join-Path $_.FullName 'node.exe') } |
        Select-Object -First 1 -ExpandProperty FullName

    if ($NestedNodeDir) {
        $NodeDir = $NestedNodeDir
        break
    }
}

$global:KanSorProjectRoot = $ProjectRoot
$global:KanSorPhpDir = $PhpDir
$global:KanSorComposerPhar = $ComposerPhar
$global:KanSorNodeDir = $NodeDir

$PathEntries = @($PhpDir)

if ($NodeDir) {
    $PathEntries += $NodeDir
}

$env:Path = "$(($PathEntries -join ';'));$env:Path"
$env:PHPRC = $PhpDir
$env:PHP_BINARY = $PhpBinary

function global:composer {
    & $env:PHP_BINARY $global:KanSorComposerPhar @args
}

function global:artisan {
    & $env:PHP_BINARY (Join-Path $global:KanSorProjectRoot 'artisan') @args
}

if ($global:KanSorNodeDir) {
    function global:node {
        & (Join-Path $global:KanSorNodeDir 'node.exe') @args
    }

    function global:npm {
        & (Join-Path $global:KanSorNodeDir 'npm.cmd') @args
    }

    function global:npx {
        & (Join-Path $global:KanSorNodeDir 'npx.cmd') @args
    }

    if (Test-Path (Join-Path $global:KanSorNodeDir 'corepack.cmd')) {
        function global:corepack {
            & (Join-Path $global:KanSorNodeDir 'corepack.cmd') @args
        }
    }
}

Write-Host "Using PHP from:"
& $env:PHP_BINARY -v

Write-Host "`nComposer version:"
composer --version

Write-Host "`nPHP ini:"
& $env:PHP_BINARY --ini

Write-Host "`nZIP extension check:"
& $env:PHP_BINARY -m | findstr /i zip

if ($NodeDir) {
    Write-Host "`nUsing Node from:"
    & (Join-Path $NodeDir 'node.exe') -v

    Write-Host "`nnpm version:"
    & (Join-Path $NodeDir 'npm.cmd') -v

    if (Test-Path (Join-Path $NodeDir 'corepack.cmd')) {
        Write-Host "`ncorepack version:"
        & (Join-Path $NodeDir 'corepack.cmd') --version
    }
} else {
    Write-Host "`nLocal Node runtime not found in .runtime. System Node/npm will be used."
}

Write-Host "`nNativePHP workflow:"
Write-Host "Run .\\use-env.ps1, then start the desktop app with php artisan native:run."
Write-Host "Do not run npm run dev directly inside nativephp\\electron unless you explicitly set the required NATIVEPHP_* environment variables."
