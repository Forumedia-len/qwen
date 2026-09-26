$global:ActiveCourtProfileVersion = '1.3.0'
$global:ActiveCourtInstalledProfilePath = $PSCommandPath
$global:ActiveCourtProfileMessages = @{
  en = @{
    AcdocsUsage = 'Usage: acdocs'
    AcdocsDescription = 'Checks documentation of the current Active Court worktree.'
    AcrootUsage = 'Usage: acroot'
    AcrootDescription = 'Changes the current directory to the current Active Court worktree root.'
    AcprofileUsage = 'Usage: acprofile [mode] [-Force] [-Language CODE]'
    AcprofileDescription = 'Installs or manages the Active Court PowerShell profile from the current worktree.'
    Modes = 'Modes:'
    Status = '  -Status, -s       Show source and installed versions.'
    Reload = '  -Reload, -r       Reload the installed profile in the current session.'
    Update = '  -Update, -u       Install from the current worktree and reload (default).'
    Rollback = '  -Rollback, -b     Restore the previous installed copy.'
    Uninstall = '  -Uninstall, -x    Remove the loader, settings and installed files.'
    Force = '  -Force, -f        Allow downgrade or same-version content replacement.'
    Language = '  -Language, -l CODE  Set en, de, ru, auto or another language code; unsupported languages fall back to en.'
    Help = 'Help: {0} h | help | -h | --help'
    CompletionSuite = 'Codeception suite'
    CompletionGroup = 'Active Court suite group'
    CompletionCommand = 'Active Court test command'
    CompletionHelp = 'Show command help'
    CompletionLanguage = 'Active Court CLI language'
  }
  de = @{
    AcdocsUsage = 'Verwendung: acdocs'
    AcdocsDescription = 'Prüft die Dokumentation des aktuellen Active Court Worktrees.'
    AcrootUsage = 'Verwendung: acroot'
    AcrootDescription = 'Wechselt zum Stammverzeichnis des aktuellen Active Court Worktrees.'
    AcprofileUsage = 'Verwendung: acprofile [Modus] [-Force] [-Language CODE]'
    AcprofileDescription = 'Installiert oder verwaltet das Active Court PowerShell-Profil aus dem aktuellen Worktree.'
    Modes = 'Modi:'
    Status = '  -Status, -s       Quell- und installierte Version anzeigen.'
    Reload = '  -Reload, -r       Installiertes Profil in dieser Sitzung neu laden.'
    Update = '  -Update, -u       Aus aktuellem Worktree installieren und neu laden (Standard).'
    Rollback = '  -Rollback, -b     Vorherige installierte Version wiederherstellen.'
    Uninstall = '  -Uninstall, -x    Loader, Einstellungen und installierte Dateien entfernen.'
    Force = '  -Force, -f        Downgrade oder Ersetzung bei gleicher Version erlauben.'
    Language = '  -Language, -l CODE  en, de, ru, auto oder anderen Sprachcode setzen; Fallback ist en.'
    Help = 'Hilfe: {0} h | help | -h | --help'
    CompletionSuite = 'Codeception-Testpaket'
    CompletionGroup = 'Active Court Testpaket-Gruppe'
    CompletionCommand = 'Active Court Testbefehl'
    CompletionHelp = 'Befehlshilfe anzeigen'
    CompletionLanguage = 'Sprache der Active Court CLI'
  }
  ru = @{
    AcdocsUsage = 'Использование: acdocs'
    AcdocsDescription = 'Проверяет документацию текущего Active Court worktree.'
    AcrootUsage = 'Использование: acroot'
    AcrootDescription = 'Переходит в корень текущего Active Court worktree.'
    AcprofileUsage = 'Использование: acprofile [режим] [-Force] [-Language КОД]'
    AcprofileDescription = 'Устанавливает профиль Active Court PowerShell из текущего worktree или управляет им.'
    Modes = 'Режимы:'
    Status = '  -Status, -s       Показать исходную и установленную версии.'
    Reload = '  -Reload, -r       Перезагрузить установленный профиль в текущем сеансе.'
    Update = '  -Update, -u       Установить из текущего worktree и перезагрузить (по умолчанию).'
    Rollback = '  -Rollback, -b     Восстановить предыдущую установленную версию.'
    Uninstall = '  -Uninstall, -x    Удалить загрузчик, настройки и установленные файлы.'
    Force = '  -Force, -f        Разрешить понижение или замену содержимого той же версии.'
    Language = '  -Language, -l КОД  Установить en, de, ru, auto или другой код; неподдерживаемые языки используют en.'
    Help = 'Справка: {0} h | help | -h | --help'
    CompletionSuite = 'Набор Codeception'
    CompletionGroup = 'Группа наборов Active Court'
    CompletionCommand = 'Команда тестов Active Court'
    CompletionHelp = 'Показать справку команды'
    CompletionLanguage = 'Язык Active Court CLI'
  }
}

function global:Get-ActiveCourtProfileLanguage {
  [CmdletBinding()]
  param()

  $settingsPath = Join-Path (Split-Path -Parent $global:ActiveCourtInstalledProfilePath) 'settings.json'
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

  $configuredLanguage = ([string] $languageProperty.Value).Trim().Replace('_', '-').ToLowerInvariant()
  $candidate = if ($configuredLanguage -eq 'auto') {
    $PSUICulture.Trim().Replace('_', '-').ToLowerInvariant()
  } else {
    $configuredLanguage
  }
  foreach ($languageCandidate in @($candidate, $candidate.Split('-')[0], 'en')) {
    if ($global:ActiveCourtProfileMessages.ContainsKey($languageCandidate)) {
      return $languageCandidate
    }
  }

  return 'en'
}

function global:Get-ActiveCourtProfileMessage {
  [CmdletBinding()]
  param(
    [Parameter(Mandatory)]
    [string] $Key,
    [object[]] $Values = @()
  )

  $template = [string] $global:ActiveCourtProfileMessages[$global:ActiveCourtProfileLanguage][$Key]
  if ($Values.Count -eq 0) {
    return $template
  }

  return [string]::Format([Globalization.CultureInfo]::InvariantCulture, $template, $Values)
}

$global:ActiveCourtProfileLanguage = Get-ActiveCourtProfileLanguage

function global:Test-ActiveCourtHelpRequest {
  [CmdletBinding()]
  param(
    [object[]] $Arguments
  )

  return $Arguments.Count -eq 1 -and $Arguments[0] -in @('h', 'help', '-h', '--help')
}

function global:New-ActiveCourtCompletionResult {
  [CmdletBinding()]
  param(
    [Parameter(Mandatory)]
    [string] $Value,
    [Parameter(Mandatory)]
    [string] $ToolTip
  )

  return New-Object -TypeName System.Management.Automation.CompletionResult -ArgumentList @(
    $Value,
    $Value,
    'ParameterValue',
    $ToolTip
  )
}

function global:Resolve-ActiveCourtCompletionTestsRoot {
  [CmdletBinding()]
  param(
    [Parameter(Mandatory)]
    [string] $ProjectRoot
  )

  $candidates = @()
  $localConfigPath = Join-Path $ProjectRoot '.actest.loc.json'
  if (Test-Path -LiteralPath $localConfigPath -PathType Leaf) {
    try {
      $localConfig = Get-Content -LiteralPath $localConfigPath -Raw | ConvertFrom-Json
      $testsRootProperty = $localConfig.PSObject.Properties['testsRoot']
      if ($null -ne $testsRootProperty -and ![string]::IsNullOrWhiteSpace([string] $testsRootProperty.Value)) {
        $configuredPath = [string] $testsRootProperty.Value
        $candidates += if ([System.IO.Path]::IsPathRooted($configuredPath)) {
          $configuredPath
        } else {
          Join-Path $ProjectRoot $configuredPath
        }
      }
    } catch {
      return $null
    }
  }

  $environmentRoot = [Environment]::GetEnvironmentVariable('AC_TESTS_ROOT')
  if (![string]::IsNullOrWhiteSpace($environmentRoot)) {
    $candidates += $environmentRoot
  }
  $candidates += @(
    (Join-Path $ProjectRoot 'tests'),
    (Join-Path (Split-Path -Parent $ProjectRoot) 'tests'),
    (Join-Path $ProjectRoot 'vendor\active-court\base-ac-dev')
  )

  foreach ($candidate in $candidates) {
    if (
      (Test-Path -LiteralPath $candidate -PathType Container) -and
      (Test-Path -LiteralPath (Join-Path $candidate 'actest.json') -PathType Leaf) -and
      (Test-Path -LiteralPath (Join-Path $candidate 'tests') -PathType Container)
    ) {
      return [System.IO.Path]::GetFullPath($candidate)
    }
  }

  return $null
}

function global:Get-ActiveCourtTestCompletionCandidates {
  [CmdletBinding()]
  param()

  $projectRoot = Resolve-ActiveCourtProjectRoot
  $testsRoot = Resolve-ActiveCourtCompletionTestsRoot -ProjectRoot $projectRoot
  if ($null -eq $testsRoot) {
    return
  }

  $suiteToolTip = Get-ActiveCourtProfileMessage -Key 'CompletionSuite'
  Get-ChildItem -LiteralPath (Join-Path $testsRoot 'tests') -Filter '*.suite.yml' -File -ErrorAction SilentlyContinue |
    ForEach-Object {
      [pscustomobject]@{
        Value = $_.Name.Substring(0, $_.Name.Length - '.suite.yml'.Length)
        ToolTip = $suiteToolTip
      }
    }

  try {
    $catalog = Get-Content -LiteralPath (Join-Path $testsRoot 'actest.json') -Raw | ConvertFrom-Json
  } catch {
    return
  }
  foreach ($catalogEntry in @(
    @{ Property = 'suiteGroups'; Message = 'CompletionGroup' },
    @{ Property = 'commands'; Message = 'CompletionCommand' }
  )) {
    $property = $catalog.PSObject.Properties[$catalogEntry.Property]
    if ($null -eq $property -or $null -eq $property.Value) {
      continue
    }
    $toolTip = Get-ActiveCourtProfileMessage -Key $catalogEntry.Message
    foreach ($item in $property.Value.PSObject.Properties) {
      [pscustomobject]@{ Value = $item.Name; ToolTip = $toolTip }
    }
  }
}

function global:Complete-ActiveCourtCommand {
  [CmdletBinding()]
  param(
    [Parameter(Mandatory)]
    [string] $CommandName,
    [AllowEmptyString()]
    [string] $WordToComplete,
    [Parameter(Mandatory)]
    [System.Management.Automation.Language.CommandAst] $CommandAst
  )

  $elements = @($CommandAst.CommandElements | ForEach-Object { $_.Extent.Text })
  $previousElement = if ($elements.Count -le 1) {
    ''
  } elseif ($WordToComplete -ne '' -and $elements[-1] -eq $WordToComplete) {
    $elements[-2]
  } else {
    $elements[-1]
  }
  $candidates = [ordered]@{}

  if ($CommandName -eq 'acprofile') {
    if ($previousElement -in @('-Language', '-l')) {
      foreach ($language in @('en', 'de', 'ru', 'auto')) {
        $candidates[$language] = Get-ActiveCourtProfileMessage -Key 'CompletionLanguage'
      }
    } else {
      foreach ($option in @(
        @{ Value = '-s'; Message = 'Status' },
        @{ Value = '-Status'; Message = 'Status' },
        @{ Value = '-r'; Message = 'Reload' },
        @{ Value = '-Reload'; Message = 'Reload' },
        @{ Value = '-u'; Message = 'Update' },
        @{ Value = '-Update'; Message = 'Update' },
        @{ Value = '-b'; Message = 'Rollback' },
        @{ Value = '-Rollback'; Message = 'Rollback' },
        @{ Value = '-x'; Message = 'Uninstall' },
        @{ Value = '-Uninstall'; Message = 'Uninstall' },
        @{ Value = '-f'; Message = 'Force' },
        @{ Value = '-Force'; Message = 'Force' },
        @{ Value = '-l'; Message = 'Language' },
        @{ Value = '-Language'; Message = 'Language' },
        @{ Value = '-h'; Message = 'CompletionHelp' },
        @{ Value = '--help'; Message = 'CompletionHelp' }
      )) {
        $candidates[$option.Value] = Get-ActiveCourtProfileMessage -Key $option.Message
      }
    }
  } elseif ($CommandName -in @('acdocs', 'acroot')) {
    foreach ($option in @('h', 'help', '-h', '--help')) {
      $candidates[$option] = Get-ActiveCourtProfileMessage -Key 'CompletionHelp'
    }
  } elseif ($CommandName -eq 'actest') {
    if ($WordToComplete -like '--tests-source=*') {
      foreach ($source in @('auto', 'local', 'package')) {
        $candidates["--tests-source=$source"] = 'Active Court tests source'
      }
    } elseif ($previousElement -eq '--tests-source') {
      foreach ($source in @('auto', 'local', 'package')) {
        $candidates[$source] = 'Active Court tests source'
      }
    } else {
      foreach ($option in @(
        'h', 'help', '-h', '--help', '--app-root=', '--tests-root=', '--tests-source=',
        '--base_url=', '--config=', '--filter', '--grep', '--group', '--skip',
        '--skip-group', '--env', '--debug', '--fail-fast', '--no-rebuild',
        '--seed', '--no-artifacts', '--coverage'
      )) {
        $candidates[$option] = if ($option -in @('h', 'help', '-h', '--help')) {
          Get-ActiveCourtProfileMessage -Key 'CompletionHelp'
        } else {
          'Active Court or Codeception option'
        }
      }

      if (!$WordToComplete.StartsWith('-')) {
        try {
          Get-ActiveCourtTestCompletionCandidates | ForEach-Object {
            if (!$candidates.Contains($_.Value)) {
              $candidates[$_.Value] = $_.ToolTip
            }
          }
        } catch {
          # Completion must remain available even when the current directory is not an Active Court worktree.
        }
      }
    }
  }

  foreach ($candidate in $candidates.GetEnumerator()) {
    if ($candidate.Key.StartsWith($WordToComplete, [System.StringComparison]::OrdinalIgnoreCase)) {
      New-ActiveCourtCompletionResult -Value $candidate.Key -ToolTip ([string] $candidate.Value)
    }
  }
}

function global:Register-ActiveCourtArgumentCompleters {
  [CmdletBinding()]
  param()

  if ($null -eq (Get-Command Register-ArgumentCompleter -ErrorAction SilentlyContinue)) {
    return
  }

  $completer = {
    param($wordToComplete, $commandAst, $cursorPosition)

    $completionCommand = Get-Command Complete-ActiveCourtCommand -ErrorAction SilentlyContinue
    if ($null -eq $completionCommand -or $commandAst.CommandElements.Count -eq 0) {
      return
    }
    $completionCommandName = [string] $commandAst.CommandElements[0].Value
    Complete-ActiveCourtCommand `
      -CommandName $completionCommandName `
      -WordToComplete ([string] $wordToComplete) `
      -CommandAst $commandAst |
      ForEach-Object { $_ }
  }

  Register-ArgumentCompleter -Native -CommandName @('actest', 'acdocs', 'acroot', 'acprofile') -ScriptBlock $completer
}

function global:Resolve-ActiveCourtProjectRoot {
  [CmdletBinding()]
  param()

  $gitCommand = Get-Command git -ErrorAction SilentlyContinue
  if ($null -eq $gitCommand) {
    throw 'Git is required to resolve the current Active Court worktree.'
  }

  $gitOutput = & $gitCommand.Source rev-parse --show-toplevel 2>$null
  $gitExitCode = $LASTEXITCODE
  $gitRoot = $gitOutput | Select-Object -First 1
  if ($gitExitCode -ne 0 -or [string]::IsNullOrWhiteSpace($gitRoot)) {
    throw 'Run the command from inside an Active Court Git worktree.'
  }

  $projectRoot = [System.IO.Path]::GetFullPath($gitRoot)
  $requiredPaths = @(
    (Join-Path $projectRoot 'config.php'),
    (Join-Path $projectRoot 'app'),
    (Join-Path $projectRoot 'core')
  )
  if (
    !(Test-Path -LiteralPath $requiredPaths[0] -PathType Leaf) -or
    !(Test-Path -LiteralPath $requiredPaths[1] -PathType Container) -or
    !(Test-Path -LiteralPath $requiredPaths[2] -PathType Container)
  ) {
    throw "Current Git worktree is not an Active Court application: $projectRoot"
  }

  return $projectRoot
}

function global:Resolve-ActiveCourtCommand {
  [CmdletBinding()]
  param(
    [Parameter(Mandatory)]
    [string] $Name,
    [string] $ProjectRoot = (Resolve-ActiveCourtProjectRoot)
  )

  $commandPath = Join-Path (Join-Path $ProjectRoot 'bin') $Name
  if (!(Test-Path -LiteralPath $commandPath -PathType Leaf)) {
    throw "Active Court command was not found: $commandPath"
  }

  return $commandPath
}

function global:actest {
  $projectRoot = Resolve-ActiveCourtProjectRoot
  $commandPath = Resolve-ActiveCourtCommand -Name 'actest' -ProjectRoot $projectRoot
  if (Test-ActiveCourtHelpRequest -Arguments $args) {
    & php $commandPath "--app-root=$projectRoot" '--help'
  } else {
    & php $commandPath "--app-root=$projectRoot" @args
  }
  $global:LASTEXITCODE = $LASTEXITCODE
}

function global:acdocs {
  if (Test-ActiveCourtHelpRequest -Arguments $args) {
    Write-Output (Get-ActiveCourtProfileMessage -Key 'AcdocsUsage')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'AcdocsDescription')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Help' -Values @('acdocs'))
    return
  }

  $projectRoot = Resolve-ActiveCourtProjectRoot
  $commandPath = Resolve-ActiveCourtCommand -Name 'check-documentation' -ProjectRoot $projectRoot
  & php $commandPath @args
  $global:LASTEXITCODE = $LASTEXITCODE
}

function global:acroot {
  if (Test-ActiveCourtHelpRequest -Arguments $args) {
    Write-Output (Get-ActiveCourtProfileMessage -Key 'AcrootUsage')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'AcrootDescription')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Help' -Values @('acroot'))
    return
  }
  if ($args.Count -gt 0) {
    throw "Unknown acroot argument: $($args[0])"
  }

  Set-Location -LiteralPath (Resolve-ActiveCourtProjectRoot)
}

function global:acprofile {
  if (Test-ActiveCourtHelpRequest -Arguments $args) {
    Write-Output (Get-ActiveCourtProfileMessage -Key 'AcprofileUsage')
    Write-Output ''
    Write-Output (Get-ActiveCourtProfileMessage -Key 'AcprofileDescription')
    Write-Output ''
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Modes')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Status')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Reload')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Update')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Rollback')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Uninstall')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Force')
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Language')
    Write-Output ''
    Write-Output (Get-ActiveCourtProfileMessage -Key 'Help' -Values @('acprofile'))
    return
  }

  $projectRoot = Resolve-ActiveCourtProjectRoot
  $installerPath = Join-Path $projectRoot 'install-profile.ps1'
  if (!(Test-Path -LiteralPath $installerPath -PathType Leaf)) {
    throw "Active Court profile installer was not found: $installerPath"
  }

  & $installerPath @args
}

Register-ActiveCourtArgumentCompleters
