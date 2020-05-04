<?php

namespace App\Http\Controllers;

use App\PengajuanSemSid;
use Illuminate\Http\Request;
use App\Mahasiswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PengajuanSemSidController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($request)
    {
        
    }

    public function ajukanSidang($emailMhs)
    {
        $mahasiswa = mahasiswa::where('email', $emailMhs)->first();

        //mahasiswa tidak ditemukan, query salah
        if(is_null($mahasiswa)){
            return view('dosen.mahasiswa', ['mahasiswa'=>FALSE, 'popMsg'=>"Mahasiswa tidak ditemukan!"]);
        }else{
            $mahasiswa = $mahasiswa->toArray();
        }

        //jika judul==null, berarti judul belum disetujui
        if(is_null($mahasiswa['judul'])){
            //jika judul belum disetujui, belum dapat seminar
            return route('dosen.mahasiswa');
            return view('dosen.maha')->with('popMsg', $mahasiswa['name']." belum dapat melakukan sidang!");
        }else{
            $pengajuan = DB::table('pengajuan_sem_sids')->where('mahasiswa', $mahasiswa['email'])
                                                        ->Where('pengaju', Auth::user()->email)
                                                        ->first();
            
            //jika ada pengajuan aktif, maka alihkan ke halaman status pengajuan                                                   
            if(isset($pengajuan->tipe_pengajuan)){
                if($pengajuan->status!=2){
                    $dataPengajuan = [
                        'namaMhs' => Mahasiswa::where('email', $pengajuan->mahasiswa)->first()->name,
                        'tanggal' => explode(" ",$pengajuan->created_at)[0],
                        'status' => $pengajuan->status,
                        'jenis' => $pengajuan->tipe_pengajuan,
                    ];
                    return view('dosen.statusPengajuan')->with([
                        'pengajuan' =>$dataPengajuan,
                        'popMsg'=>'Terdapat pengajuan yang masih aktif.',
                        'popLevel' => 'warning',
                    ]);
                }else{
                    return view('dosen.ajukanSemSed', [
                        'tipePengajuan'=>'sidang',
                        'mahasiswa'=>$mahasiswa,
                        'popMsg'=>FALSE]);
                    
                }
            //jika tidak ada pengajuan aktif
            }else{
                return view('dosen.ajukanSemSed', [
                    'tipePengajuan'=>'sidang',
                    'mahasiswa'=>$mahasiswa,
                    'popMsg'=>FALSE]);
            }
        }
    }

    public function ajukanseminar($emailMhs)
    {
        $mahasiswa = mahasiswa::where('email', $emailMhs)->first();

        //mahasiswa tidak ditemukan, query salah
        if(is_null($mahasiswa)){
            return view('dosen.mahasiswa', ['mahasiswa'=>FALSE, 'popMsg'=>"Mahasiswa tidak ditemukan!"]);
        }else{
            $mahasiswa = $mahasiswa->toArray();
        }

        //jika judul==null, berarti judul belum disetujui
        if(is_null($mahasiswa['judul'])){
            //jika judul belum disetujui, belum dapat seminar
            return route('dosen.mahasiswa');
            return view('dosen.maha')->with('popMsg', $mahasiswa['name']." belum dapat melakukan seminar!");
        }else{
            $pengajuan = DB::table('pengajuan_sem_sids')->where('mahasiswa', $mahasiswa['email'])
                                                        ->Where('pengaju', Auth::user()->email)
                                                        ->first();
            
            //jika ada pengajuan aktif, maka alihkan ke halaman status pengajuan                                                   
            if(isset($pengajuan->tipe_pengajuan)){
                if($pengajuan->status!=2){
                    
                    $dataPengajuan = [
                        'namaMhs' => Mahasiswa::where('email', $pengajuan->mahasiswa)->first()->name,
                        'tanggal' => explode(" ",$pengajuan->created_at)[0],
                        'status' => $pengajuan->status,
                        'jenis' => $pengajuan->tipe_pengajuan,
                    ];
                    return view('dosen.statusPengajuan')->with([
                        'pengajuan' =>$dataPengajuan,
                        'popMsg'=>'Terdapat pengajuan yang masih aktif.',
                        'popLevel' => 'warning',
                    ]);
                }else{
                    return view('dosen.ajukanSemSed', [
                        'tipePengajuan'=>'seminar',
                        'mahasiswa'=>$mahasiswa,
                        'popMsg'=>FALSE]);
                }
            //jika tidak ada pengajuan aktif
            }else{
                return view('dosen.ajukanSemSed', [
                    'tipePengajuan'=>'seminar',
                    'mahasiswa'=>$mahasiswa,
                    'popMsg'=>FALSE]);
            }
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($nomorTipe, $emailDosen, $emailMhs)
    {
        $pengajuan = new PengajuanSemSid;
        $pengajuan->tipe_pengajuan = $nomorTipe;
        $pengajuan->pengaju = $emailDosen;
        $pengajuan->mahasiswa = $emailMhs;
        $pengajuan->status = 0;

        if($pengajuan->save()){
            $dataPengajuan = [
                'namaMhs' => Mahasiswa::where('email', $emailMhs)->first()->name,
                'tanggal' => explode(" ",$pengajuan->created_at)[0],
                'status' => $pengajuan->status,
                'jenis' => $pengajuan->tipe_pengajuan,
            ];
        }

        //create notification
        //notif to Mahasiswa
        if($pengajuan->tipe_pengajuan==1){
            $tipe_pengajuan = "seminar";
        }else if($pengajuan->tipe_pengajuan==2){
            $tipe_pengajuan = "sidang";
        }
        $n = new NotifikasiController;
        $n->createNotif(
            "Anda telah diajukan untuk melakukan ".$tipe_pengajuan."!",
            $emailMhs,
            "#"
        );
        //notif to koorTA
        $n = new NotifikasiController;
        $n->createNotif(
            "Pengajuan seminar untuk ".
            Mahasiswa::where('email', $emailMhs)->first()->name.".",
            'dos@localhost.co',
            "#"
        );
        
        return view('dosen.statusPengajuan')->with([
            'pengajuan' =>$dataPengajuan,
            'popMsg'=>'Pengajuan berhasil!',
            'popLevel'=>'success'
        ]);
    }

    public function ajukan(Request $request){
        $this->validate($request,[
            'emailMhs' => 'required',
            'emailDosen' => 'required',
            'actionName' => 'required',
        ]);
        switch ($request['actionName']) {
            case 'seminar':
                $request['actionName'] = 1;
                break;
            case 'sidang':
                $request['actionName'] = 2;
                break;
            default:
                # code...
                break;
        }

        return $this->create($request['actionName'], $request['emailDosen'], $request['emailMhs']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\PengajuanSemSid  $pengajuanSemSid
     * @return \Illuminate\Http\Response
     */
    public function show(PengajuanSemSid $pengajuanSemSid)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\PengajuanSemSid  $pengajuanSemSid
     * @return \Illuminate\Http\Response
     */
    public function edit(PengajuanSemSid $pengajuanSemSid)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\PengajuanSemSid  $pengajuanSemSid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PengajuanSemSid $pengajuanSemSid)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\PengajuanSemSid  $pengajuanSemSid
     * @return \Illuminate\Http\Response
     */
    public function destroy(PengajuanSemSid $pengajuanSemSid)
    {
        //
    }
}
