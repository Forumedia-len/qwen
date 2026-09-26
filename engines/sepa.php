<?php

class Sepa
{
  protected $exchanges;
  protected $sender;
  protected $trans_sum;
  protected $trans_count;
  protected $uml;
  protected $numl;

  function initialize()
  {
    $this->exchanges   = array();
    $this->sender      = array();
    $this->trans_sum   = 0;
    $this->trans_count = 0;
    $this->uml         = array('Ä', 'ä', 'Ë', 'ë', 'Ď', 'ď', 'ß', 'Ö', 'ö', 'Ü', 'ü', 'ź');
    $this->numl        = array('Ae', 'ae', 'E', 'e', 'I', 'i', 'ss', 'Oe', 'oe', 'Ue', 'ue', 'Y');
  }

  function addAccountSender($account_sender)
  {
    $this->sender = array(
//      "name"        => str_replace($this->uml, $this->numl, substr($account_sender[0], 0, 70)),
      "name"        => $account_sender[0],
      "iban"        => $account_sender[1],
      "bic"         => $account_sender[2],
      "sdd"         => $account_sender[3],
      "creditor"    => $account_sender[4],
      "date_start"  => $account_sender['date_start'],
      "date_finish" => $account_sender['date_finish'],
      "sepa_type"   => $account_sender['sepa_type']
    );
  }

  // Add transaction
  function addExchange($account_receiver, $amount, $purposes)
  {
    $this->trans_sum += $amount;
    $this->trans_count++;

    $this->exchanges[] = array(
      "transaction_id" => sprintf('%010d', $account_receiver['id']),
//      "name"           => str_replace($this->uml, $this->numl, substr($account_receiver['name'], 0, 70)),
      "name"           => $account_receiver['name'],
      "iban"           => $account_receiver['iban'],
      "bic"            => $account_receiver['bic'],
      "mndtid"         => str_replace($this->uml, $this->numl, substr($account_receiver['mndtid'], 0, 35)),
      "dtofsgntr"      => $account_receiver['dtofsgntr'],
      "amount"         => $amount,
      "purposes"       => str_replace($this->uml, $this->numl, $purposes)
    );
  }

  // Make Sepa content
  function getFileContent()
  {
    ob_start();
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    ?>
<!--    <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.08" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.001.08 pain.008.001.08.xsd">-->
    <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02 pain.008.001.02.xsd">
    <CstmrDrctDbtInitn>
        <GrpHdr>
          <MsgId><?= md5($this->sender['bic'] . '00' . date('Ymd') . 'T' . date('His')) ?></MsgId>
          <CreDtTm><?= date('Y-m-d', strtotime($this->sender['date_start'])) . "T" . date('H:i:s') ?></CreDtTm>
          <NbOfTxs><?= $this->trans_count ?></NbOfTxs>
          <CtrlSum><?= number_format($this->trans_sum, 2, '.', '') ?></CtrlSum>
          <InitgPty>
            <Nm><?= $this->sender['name'] ?></Nm>
          </InitgPty>
        </GrpHdr>
        <PmtInf>
          <PmtInfId><?= mb_substr($this->sender['creditor'], 0, 20, 'UTF8') . date('YmdHis') ?></PmtInfId>
          <PmtMtd>DD</PmtMtd>
          <NbOfTxs><?= $this->trans_count ?></NbOfTxs>
          <CtrlSum><?= number_format($this->trans_sum, 2, '.', '') ?></CtrlSum>
          <PmtTpInf>
            <SvcLvl>
              <Cd>SEPA</Cd>
            </SvcLvl>
            <LclInstrm>
              <Cd>CORE</Cd>
            </LclInstrm>
            <SeqTp>RCUR</SeqTp>
          </PmtTpInf>
          <ReqdColltnDt><?= date('Y-m-d', strtotime($this->sender['date_finish'])) ?></ReqdColltnDt>
          <Cdtr>
            <Nm><?= $this->sender['name'] ?></Nm>
          </Cdtr>
          <CdtrAcct>
            <Id>
              <IBAN><?= $this->sender['iban'] ?></IBAN>
            </Id>
          </CdtrAcct>
          <CdtrAgt>
            <FinInstnId>
<!--              <BICFI>NOTPROVIDED</BICFI>-->
              <BIC><?= $this->sender['bic'] ?></BIC>
            </FinInstnId>
          </CdtrAgt>
          <ChrgBr>SLEV</ChrgBr>
          <CdtrSchmeId>
            <Id>
              <PrvtId>
                <Othr>
                  <Id><?= $this->sender['sdd'] ?></Id>
                  <SchmeNm>
                    <Prtry>SEPA</Prtry>
                  </SchmeNm>
                </Othr>
              </PrvtId>
            </Id>
          </CdtrSchmeId>
          <?php foreach ($this->exchanges as $a) { ?>
            <DrctDbtTxInf>
              <PmtId>
                <EndToEndId><?= sprintf('%010d', $a['transaction_id']) ?></EndToEndId>
              </PmtId>
              <InstdAmt Ccy="EUR"><?= $a['amount'] ?></InstdAmt>
              <DrctDbtTx>
                <MndtRltdInf>
                  <MndtId><?= $a['mndtid'] ?></MndtId>
                  <DtOfSgntr><?= $a['dtofsgntr'] ?></DtOfSgntr>
                </MndtRltdInf>
              </DrctDbtTx>
              <DbtrAgt>
                <FinInstnId>
                  <BIC><?= mb_strtoupper($a['bic'], 'UTF-8') ?></BIC>
<!--                  <BICFI>NOTPROVIDED</BICFI>-->
                </FinInstnId>
              </DbtrAgt>
              <Dbtr>
                <Nm><?= $a['name'] ?></Nm>
              </Dbtr>
              <DbtrAcct>
                <Id>
                  <IBAN><?= mb_strtoupper($a['iban'], 'UTF-8') ?></IBAN>
                </Id>
              </DbtrAcct>
              <RmtInf>
                <Ustrd><?= $a['purposes'] ?></Ustrd>
              </RmtInf>
            </DrctDbtTxInf>
            <?php
          } ?>
        </PmtInf>
      </CstmrDrctDbtInitn>
    </Document>
    <?php
    return ob_get_clean();
  }

  // Save file local
  function saveFile($filename)
  {
    $content = $this->getFileContent();

    $Dta_fp = fopen($filename, "a");
    if (!$Dta_fp) {
      $result = false;
    } else {
      $result = fwrite($Dta_fp, $content);
      fclose($Dta_fp);
    }

    return $result;
  }
}

?>