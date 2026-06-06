# Baut ein Linux-taugliches Plugin-ZIP mit Forward-Slash-Pfaden.
# NICHT Compress-Archive nutzen: das erzeugt unter Windows Backslash-Entries,
# die auf Linux/WP-CLI als ein Dateiname interpretiert werden (unbrauchbar).
# Stattdessen ZipArchive mit manuell gesetzten Forward-Slash-Entry-Namen.

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$root      = $PSScriptRoot
$slug      = 'widerrufsbutton-wc'

# Version aus Plugin-Header lesen
$header    = Get-Content (Join-Path $root "$slug.php") -Raw
$version   = [regex]::Match($header, 'Version:\s*([0-9][^\r\n]*)').Groups[1].Value.Trim()
if (-not $version) { throw "Version nicht im Plugin-Header gefunden." }

$zipPath   = Join-Path $root "$slug-$version.zip"

# Nur Plugin-Laufzeit-Dateien (keine Dev-/Repo-Artefakte)
$include = @(
    'assets',
    'docs',
    'languages',
    'src',
    'templates',
    'composer.json',
    'README.md',
    'readme.txt',
    'uninstall.php',
    "$slug.php"
)

# Ausschluss-Muster (relativ, Forward-Slash)
$excludePatterns = @(
    '\.mo$',          # kompilierte Uebersetzungen
    '/\.gitkeep$'     # Platzhalter
)

# Dateiliste sammeln
$files = New-Object System.Collections.Generic.List[string]
foreach ($item in $include) {
    $full = Join-Path $root $item
    if (Test-Path $full -PathType Container) {
        Get-ChildItem $full -Recurse -File | ForEach-Object { $files.Add($_.FullName) }
    } elseif (Test-Path $full -PathType Leaf) {
        $files.Add($full)
    }
}

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$rootPrefix = (Resolve-Path $root).Path.TrimEnd('\') + '\'
$archive = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    $count = 0
    foreach ($file in $files) {
        $rel = $file.Substring($rootPrefix.Length).Replace('\', '/')
        $skip = $false
        foreach ($pat in $excludePatterns) { if ($rel -match $pat) { $skip = $true; break } }
        if ($skip) { continue }
        $entryName = "$slug/$rel"
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive, $file, $entryName,
            [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
        $count++
    }
} finally {
    $archive.Dispose()
}

Write-Output "Gebaut: $zipPath ($count Dateien, v$version)"
