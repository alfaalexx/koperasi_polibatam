<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PeminjamanBiasa;
use App\Models\PersentaseAdmin;
use App\Models\PersentaseBunga;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\PeminjamanBiasaNotification;
use App\Mail\PeminjamanBiasaStatusNotification;
use App\Mail\PeminjamanBiasaRejectedNotification;

class PeminjamanBiasaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function form()
    {
        $biayaBungaKhusus = PersentaseBunga::where('nama', 'Bunga Pinjaman Konsumtif Khusus')->first();
        $biayaBungaBiasa = PersentaseBunga::where('nama', 'Bunga Pinjaman Konsumtif Biasa')->first();
        $biayaAdmin = PersentaseAdmin::first();
        $title = 'Formulir Permohonan Pinjaman Konsumtif Biasa';

        return view('peminjaman.biasa', [
            'title',
            'biayaBungaKhusus' => $biayaBungaKhusus,
            'biayaBungaBiasa' => $biayaBungaBiasa,
            'biayaAdmin' => $biayaAdmin
        ], compact( 'title'));
        // return view('peminjaman.biasa', compact( 'title'));
    }

    public function index()
    {
        $loans = PeminjamanBiasa::all();
        $title = 'Daftar Pinjaman Konsumtif Biasa';
        return view('PengajuanPeminjamanBiasa.index', compact('loans', 'title'));
    }


    public function create()
    {
        return view('peminjaman.biasa', [
            'title' => 'Dashboard'
        ]);
    }

    public function store(Request $request)
    {
        $messages = [
            'jumlah.max' => 'Jumlah pinjaman tidak bisa lebih dari 10 juta.',
            'jumlah.min' => 'Jumlah pinjaman tidak bisa kurang dari 3 juta.',
        ];
        $request->validate([
            // 'no_nik' => 'required|string',
            // 'alamat' => 'required|string',
            // 'nama' => 'required|string',
            // 'no_hp' => 'required|string',
            // 'bagian' => 'required|string',
            // 'dosen_staff' => 'required|string',
            // 'email' => ['required', 'email:dns'],
            // 'no_rek' => 'required',
            'alasan_pinjam' => 'required',
            'jumlah' => [
                'required',
                'numeric',
                'min:3000000',
                'max:10000000',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'duration' => 'required|integer',
            'signature' => 'required',
            // penambahan rule untuk ttd dan up_ket
            'up_ket' => 'required|file|image',
        ], $messages);

        $user_id = Auth::id();

        $amount = str_replace(",", "", $request->jumlah); // menghapus tanda koma

        $biayaBungaBiasa = PersentaseBunga::where('nama', 'Bunga Pinjaman Konsumtif Biasa')->first(); //untuk input id persentase_bunga

        $biayaAdmin = PersentaseAdmin::first();

        $loan = new PeminjamanBiasa();
        $loan->user_id = $user_id;
        $loan->biayaBunga_id = $biayaBungaBiasa->id;
        $loan->biayaAdmin_id = $biayaAdmin->id;
        // $loan->no_nik = $request->no_nik;
        // $loan->alamat = $request->alamat;
        // $loan->nama = $request->nama;
        // $loan->no_hp = $request->no_hp;
        // $loan->bagian = $request->bagian;
        // $loan->dosen_staff = $request->dosen_staff;
        // $loan->no_rek = $request->no_rek;
        // $loan->email = $request->email;
        $loan->alasan_pinjam = $request->alasan_pinjam;
        $loan->amount = $amount;
        $loan->duration = $request->duration;
        $loan->status = 'Menunggu Pengawas';

        // Upload dan simpan ttd
        if ($request->has('signature')) {
            $signature = $request->input('signature');
            $ttdPath = 'signatures/' . time() . '.png';
            $this->saveSignatureToImage($signature, public_path($ttdPath));
            $loan->ttd = $ttdPath;
        }

        // Upload dan simpan up_ket
        if ($request->file('up_ket')) {
            $upKetPath = $request->file('up_ket')->store('public/post-images');
            $loan->up_ket = $upKetPath;
        }

        $loan->save(); // Menyimpan data ke database

        return redirect()->route('dashboard_anggota')->with('success', 'Pengajuan peminjaman berhasil silahkan tunggu verifikasi.');
    }

    private function saveSignatureToImage($signatureData, $path)
    {
        $data = explode(',', $signatureData);
        $decodedImage = base64_decode($data[1]);
        $ttdPath = 'signatures/' . time() . '.png';
        Storage::disk('public')->put($ttdPath, $decodedImage);

    }

    public function show(PeminjamanBiasa $loan)
    {
        $title = 'Detail Peminjaman';
        return view('PengajuanPeminjamanBiasa.show', compact('loan', 'title'));
    }
    public function detail(PeminjamanBiasa $loan)
    {
        $title = 'Detail';
        return view('PengajuanPeminjamanBiasa.detail', compact('loan', 'title'));
    }

    public function verifyPengawas(PeminjamanBiasa $loan)
    {
        // Cek apakah user saat ini adalah bendahara
        if (Auth::user()->id_roles == 5) {
            $loan->update([
                'status' => 'Menunggu Bendahara',
            ]);

            // Mengambil data dari tabel User
            $dataUser = User::find($loan->user_id);

            // Kirim email pemberitahuan
            Mail::to($dataUser->email)->send(new PeminjamanBiasaStatusNotification($loan));


            return redirect()->route('pinjamanan.biasa.index')->with('success', 'Pengajuan Pinjaman berhasil diverifikasi oleh Pengawas');
        } else {
            return redirect()->route('pinjamanan.biasa.index')->with('error', 'Anda bukan Pengawas dan tidak memiliki izin untuk melakukan verifikasi ini');
        }
    }

    public function verifyBendahara(PeminjamanBiasa $loan)
    {
        // Cek apakah user saat ini adalah bendahara
        if (Auth::user()->id_roles == 4) {
            $loan->update([
                'status' => 'Menunggu Ketua',
            ]);

            // Mengambil data dari tabel User
            $dataUser = User::find($loan->user_id);

            // Kirim email pemberitahuan
            Mail::to($dataUser->email)->send(new PeminjamanBiasaStatusNotification($loan));


            return redirect()->route('pinjamanan.biasa.index')->with('success', 'Pengajuan Pinjaman berhasil diverifikasi oleh Bendahara');
        } else {
            return redirect()->route('pinjamanan.biasa.index')->with('error', 'Anda bukan Bendahara dan tidak memiliki izin untuk melakukan verifikasi ini');
        }
    }

    public function verifyKetua(PeminjamanBiasa $loan)
    {
        // Cek apakah user saat ini adalah ketua
        if (Auth::user()->id_roles == 1) {
            $loan->update([
                'status' => 'Disetujui',
                'repayment_date' => now()->addMonths($loan->duration),
            ]);

            // Mengambil data dari tabel User
            $dataUser = User::find($loan->user_id);

            // Kirim email pemberitahuan
            $emailData = [
                'amount' => $loan->amount,
                'no_rek_bni' => $dataUser->no_rek_bni,
                'amount_per_month' => $loan->amount_per_month,
                'duration' => $loan->duration,
            ];
            Mail::to($dataUser->email)->send(new PeminjamanBiasaNotification($emailData));

            return redirect()->route('pinjamanan.biasa.index')->with('success', 'Pengajuan Pinjaman berhasil disetujui');
        } else {
            return redirect()->route('pinjamanan.biasa.index')->with('error', 'Anda bukan Ketua dan tidak memiliki izin untuk melakukan verifikasi ini');
        }
    }

    public function reject(Request $request, PeminjamanBiasa $loan)
    {
        $request->validate([
            'keterangan_tolak' => 'required',
        ]);

        $loan->update([
            'status' => 'Ditolak',
            'keterangan_tolak' => $request->keterangan_tolak,
        ]);

        // Mengambil data dari tabel User
        $dataUser = User::find($loan->user_id);

        // Kirim email pemberitahuan
        $emailData = [
            'status' => $loan->status,
            'keterangan_tolak' => $loan->keterangan_tolak,
        ];
        Mail::to($dataUser->email)->send(new PeminjamanBiasaRejectedNotification($emailData));

        return redirect()->route('pinjamanan.biasa.index')->with('success', 'Pengajuan Pinjaman berhasil ditolak');
    }
}
