<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Donation;
use Veritrans_Config;
use Veritrans_Snap;
use Veritrans_Notification;

class DonationController extends Controller
{
    public function index()
    {
        return view('donation');
    }

    public function store()
    {
        // DB transaction mampu melakukan roolback jika terdapat proses yang salah setelah melakukan query insert ke dalam table
        // yang artinya tidak dapat menyimpan ke dalam tabel tersebut jika terjadi kesalahan
        // use $request akan mengimport data input ke dalam function
        \DB::transaction(function () use ($request) {
            $donation = Donation::create([
                'donor_name' => $request->donor_name,
                'donor_email' => $request->donor_email,
                'donation_type' => $request->donation_type,
                'amount' => floatval($request->amount),
                'note' => $request->note
            ]);

            
            // $payload yang akan digenerate menjadi snap token
            $payload = [
                'transaction_details' => [
                    'order_id' => 'SANDBOX-'.uniqid(),
                    'gross_amount' => $donation->amount,
                ],
                'customer_details' => [
                    'first_name' => $donation->name,
                    'email' => $donation->email,
                ],
                'item_details' => [
                    [
                        'id' => $donation->donation_type,
                        'price' => $donation->amount,
                        'quantity' => 1,
                        'name' => ucwords(str_replace('_', ' ', $donation->donation_type))
                    ]
                ]
            ];

            // men-generate token dari $payload
            $snapToken = Veritrans_Snap::getSnapToken($payload);

            $donation->snap_token = $snapToken;
            $donation->save();

            // jika proses store berhasil, buat suatu response
            $this->response['snap_token'] = $snapToken;
        });

        // mengirim response
        return response()->json($this->response);
    }
}
