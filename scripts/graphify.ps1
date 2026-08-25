param(
    [string]$Scope = 'all'
)

$root = Split-Path -Parent $PSScriptRoot
$outDir = Join-Path $root 'graphify-out'
$indexPath = Join-Path $outDir 'project-index.md'

if (-not (Test-Path $outDir)) {
    New-Item -ItemType Directory -Path $outDir | Out-Null
}

$skipDirs = @(
    '.git',
    'node_modules',
    'vendor',
    'bootstrap-5.3.2-dist',
    'star-admin2-free-admin-template-1.0.0'
)

$files = Get-ChildItem -Path $root -Recurse -File | Where-Object {
    $relative = $_.FullName.Substring($root.Length + 1).Replace('\\', '/')
    -not ($skipDirs | Where-Object { $relative -like "$_/*" -or $relative -eq $_ })
} | Sort-Object FullName

$lines = New-Object System.Collections.Generic.List[string]
$lines.Add('# Project Index')
$lines.Add('')
$lines.Add("Scope: $Scope")
$lines.Add('')
$lines.Add('This index is generated from the current project tree so future sessions can jump to the right files faster.')
$lines.Add('')
$lines.Add('## Top-Level Files')
$lines.Add('')

foreach ($file in $files) {
    $relative = $file.FullName.Substring($root.Length + 1).Replace('\\', '/')
    if ($relative -notmatch '/') {
        $lines.Add("- $relative")
    }
}

$lines.Add('')
$lines.Add('## Key Folders')
$lines.Add('')

$folders = @('admin', 'core', 'includes', 'laravel-app', 'prompt', 'public', 'static', 'temoke', 'TOOLS')
foreach ($folder in $folders) {
    $folderFiles = $files | Where-Object {
        $_.FullName.Substring($root.Length + 1).Replace('\\', '/') -like "$folder/*"
    } | Select-Object -First 25
    if ($folderFiles) {
        $lines.Add("### $folder")
        foreach ($file in $folderFiles) {
            $relative = $file.FullName.Substring($root.Length + 1).Replace('\\', '/')
            $lines.Add("- $relative")
        }
        $lines.Add('')
    }
}

$lines.Add('## File Count')
$lines.Add('')
$lines.Add($files.Count.ToString())

[System.IO.File]::WriteAllLines($indexPath, $lines)
Write-Host "Wrote $indexPath"
