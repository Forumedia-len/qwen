[CmdletBinding()]
param(
  [string] $TargetProfile = $PROFILE.CurrentUserAllHosts,
  [string] $InstallRoot = (
    Join-Path ([Environment]::GetFolderPath('LocalApplicationData')) 'ActiveCourt\PowerShell'
  ),
  [Alias('s')]
  [switch] $Status,
  [Alias('r')]
  [switch] $Reload,
  [Alias('u')]
  [switch] $Update,
  [Alias('f')]
  [switch] $Force,
  [Alias('b')]
  [switch] $Rollback,
  [Alias('l')]
  [string] $Language,
  [Alias('h')]
  [switch] $Help,
  [Alias('x')]
  [switch] $Uninstall
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$settingsPath = Join-Path $InstallRoot 'settings.json'
$installerMessages = @{
  en = @{
    Usage = 'Usage: .\install-profile.ps1 [mode] [-Language CODE] [-TargetProfile PATH] [-InstallRoot PATH]'
    Modes = 'Modes:'
    Status = '  -Status, -s       Show source and installed versions.'
    Reload = '  -Reload, -r       Reload the installed profile in the current session.'
    Update = '  -Update, -u       Install from the current worktree and reload (default).'
    Rollback = '  -Rollback, -b     Restore the previous installed copy.'
    Uninstall = '  -Uninstall, -x    Remove the loader, settings and installed files.'
    Help = '  -Help, -h         Show this help.'
    Modifiers = 'Modifiers:'
    Force = '  -Force, -f        Allow downgrade or same-version content replacement.'
    Language = '  -Language, -l CODE  Set en, de, ru, auto or another language code; unsupported languages fall back to en.'
    SourceVersion = 'Source version:    {0}'
    InstalledVersion = 'Installed version: {0}'
    SourceWorktree = 'Source worktree:   {0}'
    InstalledPath = 'Installed path:    {0}'
    LanguageStatus = 'Language:          {0} (effective: {1})'
    State = 'State:             {0}'
    None = 'none'
    StateNotInstalled = 'not installed'
    StateUpdateAvailable = 'update available'
    StateInstalledNewer = 'installed version is newer'
    StateDifferentContent = 'same version with different content'
    StateCurrent = 'current'
    Reloaded = 'Active Court CLI profile reloaded in the current session: {0}'
    RolledBack = 'Active Court CLI profile rolled back to version {0}.'
    Removed = 'Active Court CLI profile removed: {0}'
    Installed = 'Active Court CLI profile {0}: {1}'
    Version = 'Version: {0}'
    LanguageSelected = 'Language: {0}'
    Commands = 'Commands loaded: actest, acdocs, acroot, acprofile'
    ActionInstalled = 'installed'
    ActionReloaded = 'reloaded'
  }
  de = @{
    Usage = 'Verwendung: .\install-profile.ps1 [Modus] [-Language CODE] [-TargetProfile PFAD] [-InstallRoot PFAD]'
    Modes = 'Modi:'
    Status = '  -Status, -s       Quell- und installierte Version anzeigen.'
    Reload = '  -Reload, -r       Installiertes Profil in dieser Sitzung neu laden.'
    Update = '  -Update, -u       Aus aktuellem Worktree installieren und neu laden (Standard).'
    Rollback = '  -Rollback, -b     Vorherige installierte Version wiederherstellen.'
    Uninstall = '  -Uninstall, -x    Loader, Einstellungen und installierte Dateien entfernen.'
    Help = '  -Help, -h         Diese Hilfe anzeigen.'
    Modifiers = 'Optionen:'
    Force = '  -Force, -f        Downgrade oder Ersetzung bei gleicher Version erlauben.'
    Language = '  -Language, -l CODE  en, de, ru, auto oder anderen Sprachcode setzen; Fallback ist en.'
    SourceVersion = 'Quellversion:       {0}'
    InstalledVersion = 'Installierte Version: {0}'
    SourceWorktree = 'Quell-Worktree:     {0}'
    InstalledPath = 'Installierter Pfad: {0}'
    LanguageStatus = 'Sprache:            {0} (effektiv: {1})'
    State = 'Status:             {0}'
    None = 'keine'
    StateNotInstalled = 'nicht installiert'
    StateUpdateAvailable = 'Update verfügbar'
    StateInstalledNewer = 'installierte Version ist neuer'
    StateDifferentContent = 'gleiche Version mit unterschiedlichem Inhalt'
    StateCurrent = 'aktuell'
    Reloaded = 'Active Court CLI-Profil in dieser Sitzung neu geladen: {0}'
    RolledBack = 'Active Court CLI-Profil auf Version {0} zurückgesetzt.'
    Removed = 'Active Court CLI-Profil entfernt: {0}'
    Installed = 'Active Court CLI-Profil {0}: {1}'
    Version = 'Version: {0}'
    LanguageSelected = 'Sprache: {0}'
    Commands = 'Geladene Befehle: actest, acdocs, acroot, acprofile'
    ActionInstalled = 'installiert'
    ActionReloaded = 'neu geladen'
  }
  ru = @{
    Usage = 'Использование: .\install-profile.ps1 [режим] [-Language КОД] [-TargetProfile ПУТЬ] [-InstallRoot ПУТЬ]'
    Modes = 'Режимы:'
    Status = '  -Status, -s       Показать исходную и установленную версии.'
    Reload = '  -Reload, -r       Перезагрузить установленный профиль в текущем сеансе.'
    Update = '  -Update, -u       Установить профиль из текущего worktree и перезагрузить (по умолчанию).'
    Rollback = '  -Rollback, -b     Восстановить предыдущую установленную версию.'
    Uninstall = '  -Uninstall, -x    Удалить загрузчик, настройки и установленные файлы.'
    Help = '  -Help, -h         Показать эту справку.'
    Modifiers = 'Параметры:'
    Force = '  -Force, -f        Разрешить понижение или замену содержимого той же версии.'
    Language = '  -Language, -l КОД  Установить en, de, ru, auto или другой код; неподдерживаемые языки используют en.'
    SourceVersion = 'Исходная версия:    {0}'
    InstalledVersion = 'Установленная версия: {0}'
    SourceWorktree = 'Исходный worktree:   {0}'
    InstalledPath = 'Путь установки:      {0}'
    LanguageStatus = 'Язык:                {0} (используется: {1})'
    State = 'Состояние:          {0}'
    None = 'нет'
    StateNotInstalled = 'не установлен'
    StateUpdateAvailable = 'доступно обновление'
    StateInstalledNewer = 'установленная версия новее'
    StateDifferentContent = 'одинаковая версия с различным содержимым'
    StateCurrent = 'актуален'
    Reloaded = 'Профиль Active Court CLI перезагружен в текущем сеансе: {0}'
    RolledBack = 'Профиль Active Court CLI восстановлен до версии {0}.'
    Removed = 'Профиль Active Court CLI удалён: {0}'
    Installed = 'Профиль Active Court CLI {0}: {1}'
    Version = 'Версия: {0}'
    LanguageSelected = 'Язык: {0}'
    Commands = 'Загружены команды: actest, acdocs, acroot, acprofile'
    ActionInstalled = 'установлен'
    ActionReloaded = 'перезагружен'
  }
}

function Normalize-ActiveCourtLanguage {
  param(
    [Parameter(Mandatory)]
    [string] $Code
  )

  $normalized = $Code.Trim().Replace('_', '-').ToLowerInvariant()
  if ($normalized -eq 'auto') {
    return $normalized
  }
  if ($normalized -notmatch '^[a-z]{2,3}(-[a-z0-9]{2,8})*$') {
    throw "Invalid Active Court CLI language code: $Code"
  }

  return $normalized
}

function Get-ConfiguredActiveCourtLanguage {
  if (!(Test-Path -LiteralPath $settingsPath -PathType Leaf)) {
    return 'en'
  }

  try {
    $settings = Get-Content -LiteralPath $settingsPath -Raw | ConvertFrom-Json
  } catch {
    throw "Active Court CLI settings are invalid: $settingsPath"
  }
  $languageProperty = $settings.PSObject.Properties['language']
  if ($null -eq $languageProperty -or [string]::IsNullOrWhiteSpace([string] $languageProperty.Value)) {
    return 'en'
  }

  return Normalize-ActiveCourtLanguage -Code ([string] $languageProperty.Value)
}

function Resolve-ActiveCourtMessageLanguage {
  param(
    [Parameter(Mandatory)]
    [string] $Code
  )

  $candidate = if ($Code -eq 'auto') {
    Normalize-ActiveCourtLanguage -Code $PSUICulture
  } else {
    $Code
  }
  foreach ($languageCandidate in @($candidate, $candidate.Split('-')[0], 'en')) {
    if ($installerMessages.ContainsKey($languageCandidate)) {
      return $languageCandidate
    }
  }

  return 'en'
}

function Get-InstallerMessage {
  param(
    [Parameter(Mandatory)]
    [string] $Key,
    [object[]] $Values = @()
  )

  $template = [string] $installerMessages[$script:EffectiveLanguage][$Key]
  if ($Values.Count -eq 0) {
    return $template
  }

  return [string]::Format([Globalization.CultureInfo]::InvariantCulture, $template, $Values)
}

function Save-ActiveCourtLanguage {
  param(
    [Parameter(Mandatory)]
    [string] $Code
  )

  if (!(Test-Path -LiteralPath $InstallRoot -PathType Container)) {
    New-Item -ItemType Directory -Force -Path $InstallRoot | Out-Null
  }
  [ordered]@{ language = $Code } |
    ConvertTo-Json |
    Set-Content -LiteralPath $settingsPath -Encoding utf8
}

$languageWasSpecified = $PSBoundParameters.ContainsKey('Language')
$configuredLanguage = if ($languageWasSpecified) {
  Normalize-ActiveCourtLanguage -Code $Language
} else {
  Get-ConfiguredActiveCourtLanguage
}
$script:EffectiveLanguage = Resolve-ActiveCourtMessageLanguage -Code $configuredLanguage

if ($Help) {
  Write-Output (Get-InstallerMessage -Key 'Usage')
  Write-Output ''
  Write-Output (Get-InstallerMessage -Key 'Modes')
  Write-Output (Get-InstallerMessage -Key 'Status')
  Write-Output (Get-InstallerMessage -Key 'Reload')
  Write-Output (Get-InstallerMessage -Key 'Update')
  Write-Output (Get-InstallerMessage -Key 'Rollback')
  Write-Output (Get-InstallerMessage -Key 'Uninstall')
  Write-Output (Get-InstallerMessage -Key 'Help')
  Write-Output ''
  Write-Output (Get-InstallerMessage -Key 'Modifiers')
  Write-Output (Get-InstallerMessage -Key 'Force')
  Write-Output (Get-InstallerMessage -Key 'Language')
  return
}

if ([string]::IsNullOrWhiteSpace($TargetProfile)) {
  throw 'Target PowerShell profile path is empty.'
}
if ([string]::IsNullOrWhiteSpace($InstallRoot)) {
  throw 'Active Court profile installation directory is empty.'
}

$modeCount = @($Status, $Reload, $Update, $Rollback, $Uninstall) |
  Where-Object { $_.IsPresent } |
  Measure-Object |
  Select-Object -ExpandProperty Count
if ($modeCount -gt 1) {
  throw 'Use only one mode: Status, Reload, Update, Rollback or Uninstall.'
}
$isUpdate = $Update.IsPresent -or $modeCount -eq 0
if ($Force -and !$isUpdate) {
  throw 'Force can only be used with Update.'
}
if ($languageWasSpecified -and !$isUpdate) {
  throw 'Language can only be changed during profile installation or update.'
}

$startMarker = '# >>> Active Court CLI >>>'
$finishMarker = '# <<< Active Court CLI <<<'
$legacyStartMarker = '# >>> Active Court actest >>>'
$legacyFinishMarker = '# <<< Active Court actest <<<'
$managedBlockPattern = '(?s)' +
  [regex]::Escape($startMarker) +
  '.*?' +
  [regex]::Escape($finishMarker)
$legacyBlockPattern = '(?s)' +
  [regex]::Escape($legacyStartMarker) +
  '.*?' +
  [regex]::Escape($legacyFinishMarker)

$sourcePath = Join-Path (Join-Path $PSScriptRoot 'bin') 'active-court-profile.ps1'
$installedPath = Join-Path $InstallRoot 'active-court-profile.ps1'
$backupPath = $installedPath + '.bak'

function Get-ActiveCourtProfileVersion {
  param(
    [Parameter(Mandatory)]
    [string] $Path
  )

  if (!(Test-Path -LiteralPath $Path -PathType Leaf)) {
    return $null
  }

  $contents = Get-Content -LiteralPath $Path -Raw
  $pattern = '(?m)^\$global:ActiveCourtProfileVersion\s*=\s*''(?<version>\d+\.\d+\.\d+)''\s*$'
  $match = [regex]::Match($contents, $pattern)
  if (!$match.Success) {
    throw "Active Court profile version was not found: $Path"
  }

  return [version] $match.Groups['version'].Value
}

function Get-TargetProfileContents {
  if (Test-Path -LiteralPath $TargetProfile -PathType Leaf) {
    return Get-Content -LiteralPath $TargetProfile -Raw
  }

  return ''
}

function Remove-ActiveCourtProfileBlocks {
  param(
    [Parameter(Mandatory)]
    [string] $Contents
  )

  $contentsWithoutCurrent = [regex]::Replace($Contents, $managedBlockPattern, '')
  return [regex]::Replace($contentsWithoutCurrent, $legacyBlockPattern, '').TrimEnd()
}

function Write-TargetProfile {
  param(
    [Parameter(Mandatory)]
    [string] $Contents
  )

  $profileDirectory = Split-Path -Parent $TargetProfile
  if ($profileDirectory -and !(Test-Path -LiteralPath $profileDirectory)) {
    New-Item -ItemType Directory -Force -Path $profileDirectory | Out-Null
  }

  Set-Content -LiteralPath $TargetProfile -Value $Contents -Encoding utf8 -NoNewline
}

function Install-ActiveCourtProfileLoader {
  $profileContents = Remove-ActiveCourtProfileBlocks -Contents (Get-TargetProfileContents)
  $escapedInstalledPath = $installedPath.Replace("'", "''")
  $loader = @(
    $startMarker
    ('$activeCourtProfileSource = ''' + $escapedInstalledPath + '''')
    'if (Test-Path -LiteralPath $activeCourtProfileSource -PathType Leaf) {'
    '  . $activeCourtProfileSource'
    '}'
    $finishMarker
  ) -join [Environment]::NewLine

  $separator = if ($profileContents -eq '') {
    ''
  } else {
    [Environment]::NewLine + [Environment]::NewLine
  }
  $updatedContents = $profileContents + $separator + $loader + [Environment]::NewLine
  Write-TargetProfile -Contents $updatedContents
}

function Remove-ActiveCourtProfileFunctions {
  foreach ($functionName in @(
    'Resolve-ActiveCourtProjectRoot',
    'Resolve-ActiveCourtCommand',
    'Test-ActiveCourtHelpRequest',
    'Get-ActiveCourtProfileLanguage',
    'Get-ActiveCourtProfileMessage',
    'New-ActiveCourtCompletionResult',
    'Resolve-ActiveCourtCompletionTestsRoot',
    'Get-ActiveCourtTestCompletionCandidates',
    'Complete-ActiveCourtCommand',
    'Register-ActiveCourtArgumentCompleters',
    'actest',
    'acdocs',
    'acroot',
    'acprofile'
  )) {
    Remove-Item -LiteralPath "Function:\global:$functionName" -Force -ErrorAction SilentlyContinue
  }

  Remove-Variable -Name ActiveCourtProfileVersion -Scope Global -Force -ErrorAction SilentlyContinue
  Remove-Variable -Name ActiveCourtInstalledProfilePath -Scope Global -Force -ErrorAction SilentlyContinue
  Remove-Variable -Name ActiveCourtProfileLanguage -Scope Global -Force -ErrorAction SilentlyContinue
  Remove-Variable -Name ActiveCourtProfileMessages -Scope Global -Force -ErrorAction SilentlyContinue
}

if ($Status) {
  if (!(Test-Path -LiteralPath $sourcePath -PathType Leaf)) {
    throw "Active Court profile source was not found: $sourcePath"
  }

  $sourceVersion = Get-ActiveCourtProfileVersion -Path $sourcePath
  $installedVersion = Get-ActiveCourtProfileVersion -Path $installedPath
  $state = if ($null -eq $installedVersion) {
    Get-InstallerMessage -Key 'StateNotInstalled'
  } elseif ($sourceVersion -gt $installedVersion) {
    Get-InstallerMessage -Key 'StateUpdateAvailable'
  } elseif ($sourceVersion -lt $installedVersion) {
    Get-InstallerMessage -Key 'StateInstalledNewer'
  } elseif ((Get-FileHash -LiteralPath $sourcePath).Hash -ne (Get-FileHash -LiteralPath $installedPath).Hash) {
    Get-InstallerMessage -Key 'StateDifferentContent'
  } else {
    Get-InstallerMessage -Key 'StateCurrent'
  }

  Write-Host (Get-InstallerMessage -Key 'SourceVersion' -Values @($sourceVersion))
  Write-Host (Get-InstallerMessage -Key 'InstalledVersion' -Values @($(if ($null -eq $installedVersion) { Get-InstallerMessage -Key 'None' } else { $installedVersion })))
  Write-Host (Get-InstallerMessage -Key 'SourceWorktree' -Values @($PSScriptRoot))
  Write-Host (Get-InstallerMessage -Key 'InstalledPath' -Values @($installedPath))
  Write-Host (Get-InstallerMessage -Key 'LanguageStatus' -Values @($configuredLanguage, $script:EffectiveLanguage))
  Write-Host (Get-InstallerMessage -Key 'State' -Values @($state))
  return
}

if ($Reload) {
  if (!(Test-Path -LiteralPath $installedPath -PathType Leaf)) {
    throw "Active Court profile is not installed: $installedPath"
  }

  . $installedPath
  Write-Host (Get-InstallerMessage -Key 'Reloaded' -Values @($global:ActiveCourtProfileVersion)) -ForegroundColor Green
  return
}

if ($Rollback) {
  if (!(Test-Path -LiteralPath $backupPath -PathType Leaf)) {
    throw "Active Court profile backup was not found: $backupPath"
  }

  if (!(Test-Path -LiteralPath $InstallRoot -PathType Container)) {
    New-Item -ItemType Directory -Force -Path $InstallRoot | Out-Null
  }
  Copy-Item -LiteralPath $backupPath -Destination $installedPath -Force
  Install-ActiveCourtProfileLoader
  . $installedPath
  Write-Host (Get-InstallerMessage -Key 'RolledBack' -Values @($global:ActiveCourtProfileVersion)) -ForegroundColor Green
  return
}

if ($Uninstall) {
  $updatedContents = Remove-ActiveCourtProfileBlocks -Contents (Get-TargetProfileContents)
  if ($updatedContents -ne '') {
    $updatedContents += [Environment]::NewLine
  }
  Write-TargetProfile -Contents $updatedContents

  foreach ($path in @($installedPath, $backupPath, $installedPath + '.tmp', $settingsPath)) {
    if (Test-Path -LiteralPath $path -PathType Leaf) {
      [System.IO.File]::Delete($path)
    }
  }
  Remove-ActiveCourtProfileFunctions

  Write-Host (Get-InstallerMessage -Key 'Removed' -Values @($TargetProfile)) -ForegroundColor Green
  return
}

if (!(Test-Path -LiteralPath $sourcePath -PathType Leaf)) {
  throw "Active Court profile source was not found: $sourcePath"
}

$sourceVersion = Get-ActiveCourtProfileVersion -Path $sourcePath
$installedVersion = Get-ActiveCourtProfileVersion -Path $installedPath
$sourceHash = (Get-FileHash -LiteralPath $sourcePath -Algorithm SHA256).Hash
$installedHash = if (Test-Path -LiteralPath $installedPath -PathType Leaf) {
  (Get-FileHash -LiteralPath $installedPath -Algorithm SHA256).Hash
} else {
  $null
}

if ($null -ne $installedVersion -and $sourceVersion -lt $installedVersion -and !$Force) {
  throw "Source profile $sourceVersion is older than installed profile $installedVersion. Use -Force to downgrade."
}
if (
  $null -ne $installedVersion -and
  $sourceVersion -eq $installedVersion -and
  $sourceHash -ne $installedHash -and
  !$Force
) {
  throw "Profile content changed without a version increment ($sourceVersion). Increment ActiveCourtProfileVersion or use -Force for development."
}

$profileChanged = $sourceHash -ne $installedHash
$settingsChanged = $languageWasSpecified -or !(Test-Path -LiteralPath $settingsPath -PathType Leaf)
if ($profileChanged) {
  if (!(Test-Path -LiteralPath $InstallRoot -PathType Container)) {
    New-Item -ItemType Directory -Force -Path $InstallRoot | Out-Null
  }
  if (Test-Path -LiteralPath $installedPath -PathType Leaf) {
    Copy-Item -LiteralPath $installedPath -Destination $backupPath -Force
  }

  $temporaryPath = $installedPath + '.tmp'
  try {
    Copy-Item -LiteralPath $sourcePath -Destination $temporaryPath -Force
    Copy-Item -LiteralPath $temporaryPath -Destination $installedPath -Force
  } finally {
    if (Test-Path -LiteralPath $temporaryPath -PathType Leaf) {
      [System.IO.File]::Delete($temporaryPath)
    }
  }
}

if ($settingsChanged) {
  Save-ActiveCourtLanguage -Code $configuredLanguage
}

Install-ActiveCourtProfileLoader
. $installedPath

$action = if ($profileChanged) { Get-InstallerMessage -Key 'ActionInstalled' } else { Get-InstallerMessage -Key 'ActionReloaded' }
Write-Host (Get-InstallerMessage -Key 'Installed' -Values @($action, $installedPath)) -ForegroundColor Green
Write-Host (Get-InstallerMessage -Key 'Version' -Values @($global:ActiveCourtProfileVersion)) -ForegroundColor DarkGray
Write-Host (Get-InstallerMessage -Key 'LanguageSelected' -Values @($configuredLanguage)) -ForegroundColor DarkGray
Write-Host (Get-InstallerMessage -Key 'Commands') -ForegroundColor DarkGray

