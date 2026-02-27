<h2>Payment Receipt</h2>

<p>Bill No: {{ $payment->bill->bill_no }}</p>
<p>Patient: {{ $payment->bill->patient_name }}</p>
<p>Amount Paid: ₦{{ number_format($payment->amount,2) }}</p>
<p>Method: {{ $payment->payment_method }}</p>
<p>Date: {{ $payment->created_at }}</p>