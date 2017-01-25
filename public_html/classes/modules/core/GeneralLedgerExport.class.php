<?php

/*

How this needs to work
----------------------

Abstraction layer. Store all data in a "FairnessTNA" format, then use
different classes to export that data to each EFT foramt.

Add:


/*
Example Usage:

$gle = new GeneralLedgerExport();
$gle->setFileFormat('Simply');
    $je = new GeneralLedgerExport_JournalEntry();

    $je->setDate( time() );
    $je->setSource( 0000101 );
    $je->setComment( "Benoit, Wendy" );

    $record = new GeneralLedgerExport_Record();
    $record->setAccount( 2500 );
    $record->setType('CREDIT'); //Or Debit?
    $record->setAmount( 10.00 );

    $je->setRecord($record);

$gle->setJournalEntry($je);

$gle->compile();

$eft->save('/tmp/gl01.txt');
*/

/**
 * @package Core\GeneralLedgerExport
 */
class GeneralLedgerExport
{
    public $file_format_options = array('CSV');
    public $file_format = null; //File format
    public $set_journal_entry_errors = 0;
    public $journal_entry_error_msgs = array();

    public function __construct()
    {
        Debug::Text(' Contruct... ', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    public function isFloat($value)
    {
        if (preg_match('/^[-0-9\.]+$/', $value)) {
            return true;
        }

        return false;
    }

    public function setJournalEntry($obj)
    {
        //Make sure accounts balance.

        if ($obj->checkBalance() == true and $obj->combineRecords() == true) {
            $this->data[] = $obj;

            return true;
        } else {
            //Count errors, so we can NOT compile data if something doesn't balance
            Debug::Text(' Journal Entry did not balance: Errors: ' . $this->set_journal_entry_errors, __FILE__, __LINE__, __METHOD__, 10);
            $this->set_journal_entry_errors++;
            $this->journal_entry_error_msgs[] = $obj->journal_entry_error_msg;
        }

        return false;
    }

    public function compile()
    {
        if (!isset($this->data) or $this->set_journal_entry_errors > 0) {
            Debug::Text(' No Data, or Journal Entry did not balance: Errors: ' . $this->set_journal_entry_errors, __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        switch (strtolower($this->getFileFormat())) {
            case 'simply':
                $file_format_obj = new GeneralLedgerExport_File_Format_Simply($this->data);
                break;
            case 'quickbooks':
                $file_format_obj = new GeneralLedgerExport_File_Format_QuickBooks($this->data);
                break;
            case 'sage300':
                $file_format_obj = new GeneralLedgerExport_File_Format_Sage300($this->data);
                break;
            case 'csv':
            case 'export_csv':
                $file_format_obj = new GeneralLedgerExport_File_Format_CSV($this->data);
                break;
        }

        Debug::Text('aData Lines: ' . count($this->data), __FILE__, __LINE__, __METHOD__, 10);

        $compiled_data = $file_format_obj->_compile();
        if ($compiled_data !== false) {
            $this->compiled_data = $compiled_data;

            return true;
        }


        return false;
    }

    public function getFileFormat()
    {
        if (isset($this->file_format)) {
            return $this->file_format;
        }

        return false;
    }

    /*
    Functions to help process the data.
    */

    public function setFileFormat($format)
    {
        $this->file_format = $format;

        return true;
    }

    public function save($file_name)
    {
        //saves processed data to a file.

        if ($this->getCompiledData() !== false) {
            if (is_writable(dirname($file_name)) and !file_exists($file_name)) {
                if (file_put_contents($file_name, $this->getCompiledData()) > 0) {
                    Debug::Text('Write successfull:', __FILE__, __LINE__, __METHOD__, 10);

                    return true;
                } else {
                    Debug::Text('Write failed:', __FILE__, __LINE__, __METHOD__, 10);
                }
            } else {
                Debug::Text('File is not writable, or already exists:', __FILE__, __LINE__, __METHOD__, 10);
            }
        }

        Debug::Text('Save Failed!:', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function getCompiledData()
    {
        if (isset($this->compiled_data) and $this->compiled_data !== null and $this->compiled_data !== false) {
            return $this->compiled_data;
        }

        return false;
    }
}


/**
 * @package Core\GeneralLedgerExport
 */
class GeneralLedgerExport_JournalEntry extends GeneralLedgerExport
{
    public $journal_entry_data = null;
    public $journal_entry_error_msg = null;
    public $ignore_balance_check = false;

    public function __construct($ignore_balance_check = false)
    {
        Debug::Text(' GLE_JournalEntry Contruct... ', __FILE__, __LINE__, __METHOD__, 10);
        $this->ignore_balance_check = $ignore_balance_check;
        return true;
    }

    public function getDate()
    {
        if (isset($this->journal_entry_data['date'])) {
            return $this->journal_entry_data['date'];
        }

        return false;
    }

    public function setDate($value)
    {
        if ($value != '') {
            $this->journal_entry_data['date'] = $value;

            return true;
        }

        return false;
    }

    public function getSource()
    {
        if (isset($this->journal_entry_data['source'])) {
            return $this->journal_entry_data['source'];
        }

        return false;
    }

    public function setSource($value)
    {
        $value = trim($value);

        $this->journal_entry_data['source'] = substr($value, 0, 13);

        return true;
    }

    public function getComment()
    {
        if (isset($this->journal_entry_data['comment'])) {
            return $this->journal_entry_data['comment'];
        }

        return false;
    }

    public function setComment($value)
    {
        $value = trim($value);

        if (strlen($value) <= 39) {
            $this->journal_entry_data['comment'] = $value;

            return true;
        }

        return false;
    }

    public function setRecord($obj)
    {
        if ($obj->Validate() == true) {
            $this->journal_entry_data['records'][] = $obj;
            return true;
        }

        return false;
    }

    public function combineRecords()
    {
        //See if there are multiple records with the same type AND account
        //If so, combine them in to one.
        $records = $this->getRecords();

        $i = 0;
        $account_list = array();
        $new_records = array();
        foreach ($records as $record) {
            if (isset($account_list[$record->getType()][$record->getAccount()])) {
                $original_id = $account_list[$record->getType()][$record->getAccount()];
                Debug::Text(' Found duplicate Account, combining: ' . $i . ' with ' . $original_id . ' Type: ' . $record->getType() . ' Account: ' . $record->getAccount(), __FILE__, __LINE__, __METHOD__, 10);

                //Combine two accounts in to one.
                $new_record = new GeneralLedgerExport_Record();
                $new_record->setAccount($record->getAccount());
                $new_record->setType($record->getType());
                $new_record->setAmount($new_records[$original_id]->getAmount() + $record->getAmount());

                $new_records[$original_id] = $new_record;

                unset($new_record);
            } else {
                $account_list[$record->getType()][$record->getAccount()] = $i;

                $new_records[$i] = $record;
            }


            $i++;
        }

        $this->journal_entry_data['records'] = $new_records;

        return $this->checkBalance();
    }

    public function getRecords()
    {
        if (isset($this->journal_entry_data['records']) and $this->journal_entry_data['records'] != null) {
            return $this->journal_entry_data['records'];
        }

        return false;
    }

    public function checkBalance()
    {
        Debug::Text(' Checking Balance of Journal Entry...', __FILE__, __LINE__, __METHOD__, 10);
        $records = $this->getRecords();
        if ($records == false) {
            return false;
        }

        $debit_amount = 0;
        $credit_amount = 0;

        $i = 0;
        foreach ($records as $record) {
            Debug::Text($i . '. Type: ' . $record->getType() . ' Amount: ' . $record->getAmount() . ' Account: ' . $record->getAccount(), __FILE__, __LINE__, __METHOD__, 10);
            if ($record->getType() == 'debit') {
                $debit_amount += $record->getAmount();
            } elseif ($record->getType() == 'credit') {
                $credit_amount += $record->getAmount();
            } else {
                Debug::Text('NO ACCOUNT TYPE BAD!!', __FILE__, __LINE__, __METHOD__, 10);
            }

            $i++;
        }

        Debug::Text(' Debit Amount: ' . $debit_amount . ' Credit Amount: ' . $credit_amount, __FILE__, __LINE__, __METHOD__, 10);
        if ($debit_amount != 0 and $credit_amount != 0
            and round($debit_amount, 2) == round($credit_amount, 2)
        ) {
            Debug::Text(' JE balances!', __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        Debug::Text(' Journal Entry DOES NOT BALANCE!', __FILE__, __LINE__, __METHOD__, 10);
        if ($this->ignore_balance_check == true) {
            Debug::Text(' Skipping Balance of Journal Entry...', __FILE__, __LINE__, __METHOD__, 10);
            return true;
        } else {
            $this->journal_entry_error_msg = TTi18n::getText('Debit: %1 Credit: %2', array($debit_amount, $credit_amount));

            return false;
        }
    }
}


/**
 * @package Core\GeneralLedgerExport
 */
class GeneralLedgerExport_Record extends GeneralLedgerExport_JournalEntry
{
    public $record_data = null;

    /*
        function __construct( $options = NULL ) {
            Debug::Text(' GLE_Record Contruct... ', __FILE__, __LINE__, __METHOD__, 10);

            return TRUE;
        }
    */

    public function setType($value)
    {
        $value = strtolower($value);

        if ($value == 'credit' or $value == 'debit') {
            $this->record_data['type'] = $value;

            return true;
        }

        return false;
    }

    public function setAmount($value)
    {
        //Allow negative values, for example if someone is trying to export negative values (for things like vacation accrual)
        //Used to check: strlen( $value ) <= 10, however that would break foriegn currencies that use large amounts.
        if ($this->isFloat($value) and $value != 0) {
            $this->record_data['amount'] = $value;

            return true;
        }

        return false;
    }

    public function setAccount($value)
    {
        $value = trim($value);

        if (strlen($value) <= 100) { //Allow long account values for more job tracking.
            $this->record_data['account'] = $value;

            return true;
        }

        return false;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getType() == false or $this->getAccount() == false or $this->getAmount() == false) {
            Debug::Text(' ERROR: Validation Failed! Amount: ' . $this->getAmount() . ' Type: ' . $this->getType() . ' Account: ' . $this->getAccount(), __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        return true;
    }

    public function getType()
    {
        if (isset($this->record_data['type'])) {
            return $this->record_data['type'];
        }

        return false;
    }

    public function getAccount()
    {
        if (isset($this->record_data['account'])) {
            return $this->record_data['account'];
        }

        return false;
    }

    public function getAmount()
    {
        if (isset($this->record_data['amount'])) {
            return number_format($this->record_data['amount'], 2, '.', '');
        }

        return false;
    }
}


/**
 * @package Core\GeneralLedgerExport
 */
class GeneralLedgerExport_File_Format_Simply extends GeneralLedgerExport
{
    public $data = null;

    public function __construct($data)
    {
        Debug::Text(' General Ledger Format Simply Contruct... ', __FILE__, __LINE__, __METHOD__, 10);

        $this->data = $data;

        return true;
    }

    public function _compile()
    {
        //Processes all the data, padding it, converting dates to julian, incrementing
        //record numbers.

        $compiled_data = @implode("\r\n", $this->compileRecords());

        //Make sure the length of at least 3 records exists.
        if (strlen($compiled_data) >= 10) {
            return $compiled_data;
        }

        Debug::Text('Not enough compiled data!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    private function compileRecords()
    {
        //gets all Detail records.

        if (count($this->data) == 0) {
            Debug::Text('No data records:', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        $line1 = array();
        $line = array();
        $retval = array();
        foreach ($this->data as $journal_entry) {
            //Debug::Arr($record, 'Record Object:', __FILE__, __LINE__, __METHOD__, 10);

            $line1[] = $this->toDate($journal_entry->getDate());
            $line1[] = '"' . $journal_entry->getSource() . '"';
            $line1[] = '"' . $journal_entry->getComment() . '"';

            $line1 = implode(',', $line1);
            Debug::Text('Line 1: ' . $line1, __FILE__, __LINE__, __METHOD__, 10);
            $retval[] = $line1;

            $records = $journal_entry->getRecords();
            foreach ($records as $record) {
                $line[] = $record->getAccount();
                if ($record->getType() == 'credit') {
                    //Credits are negative.
                    $amount = number_format(($record->getAmount() * -1), 2, '.', '');
                } else {
                    $amount = $record->getAmount();
                }
                $line[] = $amount;
                $line = implode(',', $line);
                Debug::Text('Line: ' . $line, __FILE__, __LINE__, __METHOD__, 10);
                $retval[] = $line;

                unset($line);
            }
            unset($line1);
        }

        if (empty($retval) == false) {
            Debug::Text('Returning Compiled Records: ', __FILE__, __LINE__, __METHOD__, 10);
            return $retval;
        }

        return false;
    }

    private function toDate($epoch)
    {
        return date('m-d-y', $epoch);
    }
}


/**
 * @package Core\GeneralLedgerExport
 */
class GeneralLedgerExport_File_Format_CSV extends GeneralLedgerExport
{
    public $data = null;

    public function __construct($data)
    {
        Debug::Text(' General Ledger Format CSV Contruct... ', __FILE__, __LINE__, __METHOD__, 10);

        $this->data = $data;

        return true;
    }

    public function _compile()
    {
        //Processes all the data, padding it, converting dates to julian, incrementing
        //record numbers.

        $compiled_data = @implode("\r\n", $this->compileRecords());

        //Make sure the length of at least 3 records exists.
        if (strlen($compiled_data) >= 10) {
            return $compiled_data;
        }

        Debug::Text('Not enough compiled data!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    private function compileRecords()
    {
        //gets all Detail records.

        if (count($this->data) == 0) {
            Debug::Text('No data records:', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        $retval = array();
        $line1 = array();
        $line = array();
        //Column headers
        $retval[] = 'Date, Source, Comment, Account, Debit, Credit';

        foreach ($this->data as $journal_entry) {
            //Debug::Arr($record, 'Record Object:', __FILE__, __LINE__, __METHOD__, 10);

            $line1[] = $this->toDate($journal_entry->getDate());
            $line1[] = '"' . $journal_entry->getSource() . '"';
            $line1[] = '"' . $journal_entry->getComment() . '"';
            $line1[] = null;
            $line1[] = null;
            $line1[] = null;

            $line1 = implode(',', $line1);
            Debug::Text('Line 1: ' . $line1, __FILE__, __LINE__, __METHOD__, 10);
            $retval[] = $line1;

            $records = $journal_entry->getRecords();
            foreach ($records as $record) {
                for ($i = 0; $i < 3; $i++) {
                    $line[] = null;
                }

                $line[] = $record->getAccount();
                if ($record->getType() == 'debit') {
                    $line[] = $record->getAmount();
                    $line[] = null;
                } else {
                    $line[] = null;
                    $line[] = $record->getAmount();
                }

                $line = implode(',', $line);
                Debug::Text('Line: ' . $line, __FILE__, __LINE__, __METHOD__, 10);
                $retval[] = $line;

                unset($line);
            }
            unset($line1);
        }

        if (empty($retval) == false) {
            Debug::Text('Returning Compiled Records: ', __FILE__, __LINE__, __METHOD__, 10);
            return $retval;
        }

        return false;
    }

    private function toDate($epoch)
    {
        return date('m-d-y', $epoch);
    }
}

/**
 * @package Core\GeneralLedgerExport
 */
class GeneralLedgerExport_File_Format_QuickBooks extends GeneralLedgerExport
{
    public $data = null;

    public function __construct($data)
    {
        Debug::Text(' General Ledger Format QuickBooks Contruct... ', __FILE__, __LINE__, __METHOD__, 10);

        $this->data = $data;

        return true;
    }

    public function _compile()
    {
        //Processes all the data, padding it, converting dates to julian, incrementing
        //record numbers.

        $compiled_data = @implode("\r\n", $this->compileRecords());

        //Make sure the length of at least 3 records exists.
        if (strlen($compiled_data) >= 10) {
            return $compiled_data;
        }

        Debug::Text('Not enough compiled data!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    private function compileRecords()
    {
        //gets all Detail records.

        if (count($this->data) == 0) {
            Debug::Text('No data records:', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }


        /*
        !TRNS	TRNSID	TRNSTYPE		DATE	ACCNT	CLASS	AMOUNT	DOCNUM	MEMO
        !SPL	SPLID	TRNSTYPE		DATE	ACCNT	CLASS	AMOUNT	DOCNUM	MEMO
        !ENDTRNS
        TRNS			GENERAL JOURNAL 7/1/1998		Checking				650
        SPL				GENERAL JOURNAL 7/1/1998		Expense Account			-650
        ENDTRNS
        */
        //Column headers
        $retval = array();
        $retval[] = "!TRNS\tTRNSID\tTRNSTYPE\tDATE\tACCNT\tCLASS\tAMOUNT\tDOCNUM\tMEMO";
        $retval[] = "!SPL\tSPLID\tTRNSTYPE\tDATE\tACCNT\tCLASS\tAMOUNT\tDOCNUM\tMEMO";
        $retval[] = '!ENDTRNS';

        $line = array();
        foreach ($this->data as $journal_entry) {
            //Debug::Arr($record, 'Record Object:', __FILE__, __LINE__, __METHOD__, 10);

            $records = $journal_entry->getRecords();
            $i = 0;
            foreach ($records as $record) {
                if ($i == 0) {
                    $line[] = 'TRNS';
                } else {
                    $line[] = 'SPL';
                }

                $line[] = null; //TRANSID
                $line[] = 'GENERAL JOURNAL'; //TRNSTYPE
                $line[] = $this->toDate($journal_entry->getDate());

                $line[] = $record->getAccount();

                $line[] = null; //Class

                if ($record->getType() == 'debit') {
                    $line[] = $record->getAmount();
                } else {
                    $line[] = ($record->getAmount() * -1); //Credits are negative.
                }

                $line[] = null; //DOCNUM
                $line[] = $journal_entry->getComment(); //Memo

                $line = implode("\t", $line);
                Debug::Text('Line: ' . $line, __FILE__, __LINE__, __METHOD__, 10);
                $retval[] = $line;

                unset($line);

                $i++;
            }

            $retval[] = 'ENDTRNS';
        }

        if (isset($retval)) {
            Debug::Text('Returning Compiled Records: ', __FILE__, __LINE__, __METHOD__, 10);
            return $retval;
        }

        return false;
    }

    private function toDate($epoch)
    {
        return date('m/d/y', $epoch);
    }
}

/**
 * @package Core\GeneralLedgerExport
 */
class GeneralLedgerExport_File_Format_Sage300 extends GeneralLedgerExport
{
    public $data = null;

    public function __construct($data)
    {
        Debug::Text(' General Ledger Format Sage300 Contruct... ', __FILE__, __LINE__, __METHOD__, 10);

        $this->data = $data;

        return true;
    }

    public function _compile()
    {
        //Processes all the data, padding it, converting dates to julian, incrementing
        //record numbers.

        $compiled_data = @implode("\r\n", $this->compileRecords());

        //Make sure the length of at least 3 records exists.
        if (strlen($compiled_data) >= 10) {
            return $compiled_data;
        }

        Debug::Text('Not enough compiled data!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    private function compileRecords()
    {
        //gets all Detail records.

        if (count($this->data) == 0) {
            Debug::Text('No data records:', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }
        /*
        RECTYPE – this is to identify which of the three parts it is 1 = Journal_Header 2 = Journal_Details, 3 = Journal_Detail_Optional_Fields
        BATCHID – This is the Batch Number and this can be set to “000001” because this just inherits the batch number that you are importing into.
        BTCHENTRY – This is the entry number so if there are more than one entries going into the batch you would increment by one, in this case it is just going to be one entry so the values can be set to “00001”
        SRCELEDGER – This can be set to “GL” in this case.  Used to identify where the entry came from.
        SRCETYPE – This can be set to “JE” this is the type of entry.
        DATEENTRY – This is the date of the entry date format is YYYYMMDD
        JOURNALID – this is the same and the BTCHENTRY in the Journal_Header
        TRANSNBR – this is to identify the line of the journal entry and it increments by 20
        ACCTID – this is the account number
        TRANSAMT – This is the transaction amount positive DEBIT negative is a CREDIT*
        */

        //Column headers
        $retval = array();
        $retval[] = '"RECTYPE","BATCHID","BTCHENTRY","SRCELEDGER","SRCETYPE","DATEENTRY"';
        $retval[] = '"RECTYPE","BATCHNBR","JOURNALID","TRANSNBR","ACCTID","TRANSAMT"';
        $retval[] = '"RECTYPE","BATCHNBR","JOURNALID","TRANSNBR","OPTFIELD"';

        $line = array();
        $line1 = array();
        $entry_number = 1;
        foreach ($this->data as $journal_entry) {
            Debug::Arr($journal_entry, 'Record Object:', __FILE__, __LINE__, __METHOD__, 10);

            $line1[] = 1; //RecType: Header
            $line1[] = '000001'; //BatchID
            $line1[] = str_pad($entry_number, 5, '0', STR_PAD_LEFT); //BatchEntry
            $line1[] = 'GL'; //SRCELEDGER
            $line1[] = 'JE'; //SRCETYPE
            $line1[] = $this->toDate($journal_entry->getDate());

            $line1 = implode('","', $line1);
            Debug::Text('Line 1 (Header): ' . $line1, __FILE__, __LINE__, __METHOD__, 10);
            $retval[] = '"' . $line1 . '"';

            $transaction_number = 20;
            $records = $journal_entry->getRecords();
            foreach ($records as $record) {
                $line[] = 2; //RecType: Details
                $line[] = '000001'; //BatchID
                $line[] = str_pad($entry_number, 5, '0', STR_PAD_LEFT); //BatchEntry
                $line[] = str_pad($transaction_number, 5, '0', STR_PAD_LEFT); //TransNBR

                $line[] = $record->getAccount();
                if ($record->getType() == 'debit') {
                    $line[] = $record->getAmount(); //Positive on DEBIT
                } else {
                    $line[] = ($record->getAmount() * -1); //Negative on CREDIT.
                }

                $line = implode('","', $line);
                Debug::Text('Line: ' . $line, __FILE__, __LINE__, __METHOD__, 10);
                $retval[] = '"' . $line . '"';

                $transaction_number += 20; //Increases by 20 each time.
                unset($line);
            }
            unset($line1);

            $entry_number++;
        }

        if (isset($retval)) {
            Debug::Text('Returning Compiled Records: ', __FILE__, __LINE__, __METHOD__, 10);
            return $retval;
        }

        return false;
    }

    private function toDate($epoch)
    {
        return date('Ymd', $epoch);
    }
}
