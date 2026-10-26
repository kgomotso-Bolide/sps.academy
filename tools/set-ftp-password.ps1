# Sets the new Xneelo SFTP password in EVERY deploy repository at once.
#
# All the academies and the BEE tool share one Xneelo SFTP login, but GitHub
# secrets are per repository, and the repositories call the secret by
# different names (they were created at different times). Changing the
# password in the Xneelo panel therefore means updating eleven secrets; this
# does all of them from one prompt.
#
# Run it in PowerShell from anywhere:
#   powershell -ExecutionPolicy Bypass -File tools\set-ftp-password.ps1
#
# The password is typed into a hidden prompt, twice. It is never written to
# disk, never echoed, and never needs to be pasted into a chat. Needs the
# GitHub CLI signed in (gh auth status) with access to both owners.
#
# When a repository is added, add it to the list below with its secret name.

$targets = @(
  @{ Repo = 'kgomotso-Bolide/sps.academy';          Secret = 'SPS_FTP_PASSWORD' },
  @{ Repo = 'kgomotso-Bolide/fungi.academy';        Secret = 'FUNGI_FTP_PASSWORD' },
  @{ Repo = 'kgomotso-Bolide/maziv.academy';        Secret = 'MAZIV_FTP_PASSWORD' },
  @{ Repo = 'kgomotso-Bolide/equinix.academy';      Secret = 'EQUINIX_FTP_PASSWORD' },
  @{ Repo = 'CentenaryNetworks/tracker';            Secret = 'TRACKER_FTP_PASSWORD' },
  @{ Repo = 'CentenaryNetworks/m-t';                Secret = 'MT_FTP_PASSWORD' },
  @{ Repo = 'CentenaryNetworks/cricketSA';          Secret = 'CRICKETSA_FTP_PASSWORD' },
  @{ Repo = 'CentenaryNetworks/Inhance';            Secret = 'TRACKER_FTP_PASSWORD' },
  @{ Repo = 'CentenaryNetworks/angloamerican';      Secret = 'TRACKER_FTP_PASSWORD' },
  @{ Repo = 'CentenaryNetworks/altCapitalPartners'; Secret = 'TRACKER_FTP_PASSWORD' },
  @{ Repo = 'CentenaryNetworks/beetool';            Secret = 'TRACKER_FTP_PASSWORD' }
)

function Read-Plain([string]$prompt) {
  $s = Read-Host -Prompt $prompt -AsSecureString
  $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s)
  try { return [Runtime.InteropServices.Marshal]::PtrToStringAuto($b) }
  finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) }
}

$pw  = Read-Plain 'New Xneelo SFTP password'
$pw2 = Read-Plain 'Same password again'
if ($pw -ne $pw2) { Write-Host 'The two entries are different. Nothing was changed.' -ForegroundColor Red; exit 1 }
if ($pw.Trim() -ne $pw -or $pw.Length -lt 8) { Write-Host 'That password is empty, too short, or has spaces at the ends. Nothing was changed.' -ForegroundColor Red; exit 1 }

$failed = 0
foreach ($t in $targets) {
  # --body rather than a pipe: Windows PowerShell adds a line break to piped
  # text, and a password with a stray newline fails at the server for no
  # visible reason.
  gh secret set $t.Secret --repo $t.Repo --body $pw *> $null
  if ($LASTEXITCODE -eq 0) { Write-Host ('  ok      {0,-40} {1}' -f $t.Repo, $t.Secret) -ForegroundColor Green }
  else { Write-Host ('  FAILED  {0,-40} {1}' -f $t.Repo, $t.Secret) -ForegroundColor Red; $failed++ }
}
$pw = $null; $pw2 = $null

if ($failed) { Write-Host "$failed repository(ies) were NOT updated - their deploys will fail until they are." -ForegroundColor Red; exit 1 }
Write-Host 'All repositories updated.' -ForegroundColor Green
