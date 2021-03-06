<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\HewanRequest;

use App\Models\Pasien;
use App\Models\Hewan;
use Auth;

class PeliharaanController extends Controller
{
		public function __construct()
		{
				$this->middleware('auth');  //user guard
		}

		protected function user()
		{
			return auth()->user();
		}

		public function store(HewanRequest $request, $id)
		{
      $kodeHewan = $this->setKodeHewan($id);
			$hewan = new Hewan;
      $hewan->pasien_id = $id;
      $hewan->kode      = $kodeHewan;
			$hewan->nama      = $request->namahewan;
      $hewan->jenis     = $request->jenishewan;
      $hewan->gender    = $request->genderhewan;
      $hewan->ras       = $request->rashewan;
      $hewan->warna     = $request->warnabulu;
      $hewan->created_by    = $this->user()->name;
      $hewan->updated_by    = $this->user()->name;
      $hewan->save();
      return redirect('pasien/'.$id);
		}

    /*
    * buat kode hewan gabungan dari kode pasien concatinate dengan jumlah hewan
    */
    public function setKodeHewan($id)
    {
      $kodePasien = Pasien::select('kode')->where('id', $id)->get();
      foreach ($kodePasien as $value) {
        $kode = $value->kode;
      }

      $kodeHewan = $kode.'.'.$this->countKodeHewan($id);
      return $kodeHewan;
    }

    /*
    * hitung jumlah hewan yang dimiliki pasien
    * tambah jumlah hewan sebagai kode hewan baru
    */
    public function countKodeHewan($id)
    {
      $data = Hewan::where('pasien_id', $id)->withTrashed()->get();
      if( $data->isEmpty() )
      {
        return 1;
      }else
      {
        foreach ($data as $dataHewan) {
          $peliharaan[] = $dataHewan;
        }

        (int)$jumlahHewan = count($peliharaan);
        // dump(++$jumlahHewan);
        // dd();
        return ++$jumlahHewan;
      }
    }

		public function edit($pasien, $hewan)
		{
			$pasien = Pasien::findOrFail($pasien);
			$peliharaan = Hewan::findOrFail($hewan);
			return view('/admin.pages.EditPeliharaan', ['pasien'=>$pasien, 'peliharaan'=>$peliharaan]);
		}

		public function update(HewanRequest $request, $id)
    {
    	$find_id = Hewan::findOrFail($id);
    	Hewan::findOrFail($id)->update([
    		'nama'			=> $request->namahewan,
    		'jenis'	   	=> $request->jenishewan,
    		'gender'	  => $request->genderhewan,
    		'ras'		 		=> $request->rashewan,
    		'warna'	 		=> $request->warnabulu,
    		'updated_by'	 => $this->user()->name
    	]);

    	return redirect('pasien/' . $find_id->pasien_id);
    }

    public function delete($pasien, $hewan)
    {
    	$peliharaan = Hewan::findOrFail($hewan);
    	$peliharaan->delete();
    	return redirect('pasien/'.$pasien);
    }
}
