<?php
date_default_timezone_set('Asia/Manila');
$conn = new mysqli("localhost","root","","erp_indang");
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$type=$_GET['type']??null;
$report=$_GET['report']??"Sales Summary";
$from=$_GET['from']??null;
$to=$_GET['to']??null;

$from_text=$from?date('M d, Y',strtotime($from)):"Beginning";
$to_text=$to?date('M d, Y',strtotime($to)):date('M d, Y');
$generated_on=date('M d, Y H:i:s');

// -------------------- Query --------------------
$where=$from && $to ? "WHERE o.created_at BETWEEN '$from 00:00:00' AND '$to 23:59:59'" : "";
$sql="SELECT o.order_id,o.total_amount,o.created_at,CASE WHEN EXISTS(SELECT 1 FROM payments p WHERE p.order_id=o.id AND p.status='paid') THEN 'Paid' ELSE 'Pending' END AS payment_status FROM orders o $where ORDER BY o.created_at DESC";

$result=$conn->query($sql);
$data=[];
if($result && $result->num_rows>0){
    while($row=$result->fetch_assoc()){
        $row['total_amount']=number_format((float)$row['total_amount'],2);
        $row['created_at']=date('Y-m-d H:i:s',strtotime($row['created_at']));
        $data[]=$row;
    }
}

// -------------------- CSV/Excel --------------------
if($type==='csv'||$type==='excel'){
    header('Content-Type:text/csv; charset=UTF-8');
    header('Content-Disposition:attachment; filename="Tire_Supply_Report_'.date('Ymd_His').'.csv"');

    $out=fopen('php://output','w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ["Tire Supply BI"]);
    fputcsv($out, ["$report Report"]);
    fputcsv($out, ["Date Range:", "$from_text - $to_text"]);
    fputcsv($out, ["Generated On:", $generated_on]);
    fputcsv($out, []);
    fputcsv($out, ["ORDER ID","Total Amount","Status","Created At"]);

    foreach($data as $r){
        fputcsv($out, ["#".$r['order_id'],"₱".$r['total_amount'],$r['payment_status'],$r['created_at']]);
    }
    fclose($out);
    exit;
}

// -------------------- PDF --------------------
elseif($type==='pdf'){
    require('fpdf/fpdf.php');
    class PDF extends FPDF{
        function FancyTable($header,$data){
            $this->SetFillColor(50,50,150);
            $this->SetTextColor(255);
            $this->SetFont('Arial','B',12);
            $w=[40,50,40,50]; // Fixed column widths

            foreach($header as $i=>$col) $this->Cell($w[$i],10,utf8_decode($col),1,0,'C',true);
            $this->Ln();

            $this->SetFillColor(245,245,245);
            $this->SetTextColor(0);
            $this->SetFont('Arial','',12);
            $fill=false;
            foreach($data as $row){
                $this->Cell($w[0],10,"#".$row['order_id'],1,0,'C',$fill);
                $this->Cell($w[1],10,"₱".$row['total_amount'],1,0,'C',$fill);
                $this->SetTextColor($row['payment_status']=='Paid'?0:128, $row['payment_status']=='Paid'?128:140, 0); // green/orange
                $this->Cell($w[2],10,$row['payment_status'],1,0,'C',$fill);
                $this->SetTextColor(0);
                $this->Cell($w[3],10,$row['created_at'],1,0,'C',$fill);
                $this->Ln();
                $fill=!$fill;
            }
        }
    }
    $pdf=new PDF('P','mm','A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'Tire Supply BI',0,1,'C');
    $pdf->SetFont('Arial','',14);
    $pdf->Cell(0,10,"$report Report",0,1,'C');
    $pdf->Cell(0,8,"Date Range: $from_text - $to_text",0,1,'C');
    $pdf->Cell(0,8,"Generated On: $generated_on",0,1,'C');
    $pdf->Ln(5);

    if(!empty($data)) $pdf->FancyTable(["ORDER ID","Total Amount","Status","Created At"],$data);
    else $pdf->Cell(0,10,"No data available for the selected period.",1,1,'C');

    $pdf->Output("Tire_Supply_Report_".date('Ymd_His').".pdf",'D');
    exit;
}

else die("Invalid export type.");
?>
