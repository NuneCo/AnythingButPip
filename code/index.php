<?php
/*
  SETUP:
    * Need to create whatever file is pointed to by cGlobals::FilePath_forData() . 'client-ids.txt'
      and make sure this code has permission to write to it.
    * Need to modify the values returned by cGlobals to suit your folder-structure.
  HISTORY:
    2020-03-21 written for Tessa/resist.guide
*/

$fErrLevel = 0
    | E_ALL
    | E_STRICT
    ;
error_reporting($fErrLevel);
if (!ini_get('display_errors')) {
    ini_set('display_errors', 1);
}

class cGlobals {
    static public function FilePath_forAccount() : string { return dirname(__DIR__,2).'/'; }
    static public function FilePath_forData() : string {
        return self::FilePath_forAccount() . 'site/var/tos/';
    }
}
/*
$kfpAcctRoot = dirname(__DIR__,2);

$kfpDataFiles = $kfpAcctRoot.'/var/tos/';
*/
class cReadStatus {

    public $vContent;
    public $bSuccess = FALSE;
    
    public function SetContent($vContent) {
        $this->cContent = $vContent;
        $this->bSuccess = TRUE;
    }
    public function ClearContent() {
        $this->vContent = NULL;
        $this->bSuccess = FALSE;
    }
    public function GetContent() { return $this->vContent; }

}

abstract class cTextTable {
    public function __construct(string $fpBase) { $this->SetBasePath($fpBase); }

    // ++ PROPERTIES ++ //

    private $fpBase;
    protected function SetBasePath(string $fp) { $this->fpBase = $fp; }
    protected function GetBasePath() : string { return $this->fpBase; }

    private $rFile;
    protected function SetHandle($r) { $this->rFile = $r; }
    protected function GetHandle() { return $this->rFile; }

    // -- PROPERTIES -- //
    // ++ CONFIG ++ //

    abstract protected function FileName() : string;
    abstract protected function FileMode() : string;
    
    // -- CONFIG -- //
    // ++ CALCULATIONS ++ //
    
    protected function Filespec() : string { return $this->GetBasePath() . $this->FileName(); }
    
    // -- CALCULATIONS -- //
    // ++ ACTION ++ //
    
    public function Open() {
        $this->SetHandle(
          fopen(
            $this->Filespec(),
            $this->FileMode()
            )
          );
    }
    public function Shut() { fclose($this->GetHandle()); }
    public function Rewind() { fseek($this->GetHandle(),0,SEEK_END); }
    public function Forwind() { fseek($this->GetHandle(),0,SEEK_SET); }
    public function ReadLine() : cReadStatus {
        $os = new cReadStatus();
        $s = fgets($this->GetHandle());
        if ($s === FALSE) {
            $os->ClearContent();
        } else {
            $os->SetContent($s);
        }
        return $os;
    }
    public function ReadCSVRow() : cReadStatus {
        $os = new cReadStatus();
        $ar = fgetcsv($this->GetHandle());
        if ($ar === FALSE) {
            $os->ClearContent();
        } else {
            $os->SetContent($ar);
        }
        return $os;
    }
    public function WriteCSVRow(array $ar) {
        fputcsv($this->GetHandle(),$ar);
        // TODO: if return value is FALSE, then writing failed.
    }

}

class cHashIndex extends cTextTable {
    protected function FileName() : string { return 'client-ids.txt'; }
    protected function FileMode() : string { return 'r+'; }  // r+w, start at beginning
    
    // ++ READ ++ //
    
    public function IndexExists($sIndex) : bool {
        // this can probably be done faster with grep, but I'm trying to get a functional module working quickly
        // When switched to using grep, change FileMode() to 'w'.
        
        $this->Rewind();
        $bFound = FALSE;
        $os = $this->ReadCSVRow();
        while ($os->bSuccess && !$bFound) {
            $ar = $os->GetContent();
            $bFound = ($ar[0] == $sIndex);
            $os = $this->ReadCSVRow();
        }
        return $bFound;
    }
    public function SaveIndex($sIndex,$sFull) {
        $this->Forwind();  // go to EOF
        $ar = array($sIndex,$sFull);
        $this->WriteCSVRow($ar);
    }
}

class cClientData {
    public $sFull;
    public $sHash;
}
class cClient {

    private $oFile = NULL;
    protected function GetFile() : cHashIndex {
        if (is_null($this->oFile)) {
            $this->oFile = new cHashIndex(cGlobals::FilePath_forData());
        }
        return $this->oFile;
    }
    
    protected function GetCurrent() : cClientData {
        $sAddr = $_SERVER["REMOTE_ADDR"];
        $sBrowser = $_SERVER["HTTP_USER_AGENT"];
        
        $sIdentity = $sAddr.' '.$sBrowser;
              
        
        // ok to change algorithm (crc32) to something else; just need to remove the old file.
        $sHash = hash('crc32',$sIdentity,FALSE); // FALSE = output hexadecimal text
        
        $oData = new cClientData();
        $oData->sFull = $sIdentity;
        $oData->sHash = $sHash;

        return $oData;
    }
    public function CurrentIsRegistered() : bool {
        $oFile = $this->GetFile();
        $oFile->Open();
        return $oFile->IndexExists($this->GetCurrent()->sHash);
    }
    public function RegisterCurrent() {
        if ($this->CurrentIsRegistered()) {
            // TODO: log an error here, someone may be trying to mess with the system
        } else {
            $oData = $this->GetCurrent();
            $this->GetFile()->SaveIndex(
                $oData->sHash,
                $oData->sFull
                );
        }
    }
}

$oClient = new cClient();
if ($oClient->CurrentIsRegistered()) {
    $doRedir = TRUE;
} else {
    if (array_key_exists('btnAccept',$_REQUEST)) {
        $oClient->RegisterCurrent();
        $doRedir = TRUE;
    } else {
        $doRedir = FALSE;
    }
}
if ($doRedir) {
    // redirect to main content
    echo 'REDIRECT TO MAIN CONTENT'; 
} else {
    // display the ToS
    echo 'DISPLAY ToS HERE';
    // display the [I HAVE READ THIS] button
    echo <<<__END__
<form method=post><input type=submit name=btnAccept value="I Have Read This"></form>
__END__;
}
