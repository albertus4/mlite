<?php
namespace Plugins\Pasien;

use Systems\AdminModule;

class Admin extends AdminModule
{
    private $_uploads = WEBAPPS_PATH.'/berkasrawat/pages/upload';
    protected $assign = [];

    public function navigation()
    {
        return [
            'Kelola'   => 'manage',
        ];
    }

    public function getManage()
    {
      if($this->settings->get('settings.versi_beta') == 'ya') {
        $this->_addHeaderFilesBeta();
        $this->assign['penjab'] = $this->db('penjab')->where('status', '1')->toArray();
        $this->assign['stts_nikah'] = $this->core->getEnum('pasien', 'stts_nikah');
        $this->assign['gol_darah'] = $this->core->getEnum('pasien', 'gol_darah');
        $this->assign['agama'] = array('ISLAM', 'KRISTEN', 'PROTESTAN', 'HINDU', 'BUDHA', 'KONGHUCU', 'KEPERCAYAAN');
        $this->assign['pnd'] = $this->core->getEnum('pasien', 'pnd');
        $this->assign['keluarga'] = $this->core->getEnum('pasien', 'keluarga');
  
        return $this->draw('manage.beta.html', ['pasien' => $this->assign]);  
      }else {
        $this->core->addJS(url(MODULES.'/dashboard/js/admin/webcam.js?v={$mlite.version}'));
        $this->_addHeaderFiles();

        $perpage = '10';

        $totalRecords = $this->db('pasien')
          ->select('no_rkm_medis')
          ->toArray();
        $jumlah_data    = count($totalRecords);
  			$offset         = 10;
  			$jml_halaman    = ceil($jumlah_data / $offset);
        $halaman    = 1;

        $rows = $this->db('pasien')
          ->desc('no_rkm_medis')
          ->offset(0)
          ->limit($perpage)
          ->toArray();

        $pasien = [];
        foreach ($rows as $row) {
          $row['cekbynokartu'] = url([ADMIN, 'pasien', 'vclaim_bynokartu', $row['no_peserta'], date('Y-m-d')]);
          $row['cekbynik'] = url([ADMIN, 'pasien', 'vclaim_bynik', $row['no_ktp'], date('Y-m-d')]);
          $pasien[] = $row;
        }

        $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
        $cek_pcare = $this->db('mlite_modules')->where('dir', 'pcare')->oneArray();

        return $this->draw('manage.html', [
          'pasien' => $pasien,
          'halaman' => $halaman,
          'jumlah_data' => $jumlah_data,
          'jml_halaman' => $jml_halaman,
          'cek_vclaim' => $cek_vclaim,
          'cek_pcare' => $cek_pcare,
          'offset' => $offset,
          'admin_mode' => $this->settings->get('settings.admin_mode')
        ]);
      }
    }

    public function anyDisplay()
    {
        $this->_addHeaderFiles();

        $perpage = '10';

        $totalRecords = $this->db('pasien')->select('no_rkm_medis')->toArray();
        $jumlah_data    = count($totalRecords);
  			$offset         = 10;
  			$jml_halaman    = ceil($jumlah_data / $offset);
        $halaman    = 1;

        if(isset($_POST['cari'])) {
          if(isset($_POST['halaman']) && $_POST['halaman'] !='') {
            $_offset = (($_POST['halaman'] - 1) * $perpage);
            $totalRecords = $this->db('pasien')
              ->select('no_rkm_medis')
              ->like('no_rkm_medis', '%'.$_POST['cari'].'%')
              ->orLike('nm_pasien', '%'.$_POST['cari'].'%')
              ->orLike('alamat', '%'.$_POST['cari'].'%')
              ->orLike('no_ktp', '%'.$_POST['cari'].'%')
              ->orLike('no_peserta', '%'.$_POST['cari'].'%')
              ->orLike('no_tlp', '%'.$_POST['cari'].'%')
              ->toArray();
            $rows = $this->db('pasien')
              ->like('no_rkm_medis', '%'.$_POST['cari'].'%')
              ->orLike('nm_pasien', '%'.$_POST['cari'].'%')
              ->orLike('alamat', '%'.$_POST['cari'].'%')
              ->orLike('no_ktp', '%'.$_POST['cari'].'%')
              ->orLike('no_peserta', '%'.$_POST['cari'].'%')
              ->orLike('no_tlp', '%'.$_POST['cari'].'%')
              ->desc('no_rkm_medis')
              ->offset($_offset)
              ->limit($perpage)
              ->toArray();
            $jumlah_data = count($totalRecords);
            $jml_halaman = ceil($jumlah_data / $offset);
            $halaman = $_POST['halaman'];
          } else {
            $totalRecords = $this->db('pasien')
              ->select('no_rkm_medis')
              ->like('no_rkm_medis', '%'.$_POST['cari'].'%')
              ->orLike('nm_pasien', '%'.$_POST['cari'].'%')
              ->orLike('alamat', '%'.$_POST['cari'].'%')
              ->orLike('no_ktp', '%'.$_POST['cari'].'%')
              ->orLike('no_peserta', '%'.$_POST['cari'].'%')
              ->orLike('no_tlp', '%'.$_POST['cari'].'%')
              ->toArray();
            $rows = $this->db('pasien')
              ->like('no_rkm_medis', '%'.$_POST['cari'].'%')
              ->orLike('nm_pasien', '%'.$_POST['cari'].'%')
              ->orLike('alamat', '%'.$_POST['cari'].'%')
              ->orLike('no_ktp', '%'.$_POST['cari'].'%')
              ->orLike('no_peserta', '%'.$_POST['cari'].'%')
              ->orLike('no_tlp', '%'.$_POST['cari'].'%')
              ->desc('no_rkm_medis')
              ->offset(0)
              ->limit($perpage)
              ->toArray();
            $jumlah_data = count($totalRecords);
      			$jml_halaman = ceil($jumlah_data / $offset);
          }
        }elseif(isset($_POST['halaman'])){
          if(isset($_POST['cari']) && $_POST['cari'] !='') {
            $_offset = (($_POST['halaman'] - 1) * $perpage);
            $totalRecords = $this->db('pasien')
              ->select('no_rkm_medis')
              ->like('no_rkm_medis', '%'.$_POST['cari'].'%')
              ->orLike('nm_pasien', '%'.$_POST['cari'].'%')
              ->orLike('alamat', '%'.$_POST['cari'].'%')
              ->orLike('no_ktp', '%'.$_POST['cari'].'%')
              ->orLike('no_peserta', '%'.$_POST['cari'].'%')
              ->orLike('no_tlp', '%'.$_POST['cari'].'%')
              ->toArray();
            $rows = $this->db('pasien')
              ->like('no_rkm_medis', '%'.$_POST['cari'].'%')
              ->orLike('nm_pasien', '%'.$_POST['cari'].'%')
              ->orLike('alamat', '%'.$_POST['cari'].'%')
              ->orLike('no_ktp', '%'.$_POST['cari'].'%')
              ->orLike('no_peserta', '%'.$_POST['cari'].'%')
              ->orLike('no_tlp', '%'.$_POST['cari'].'%')
              ->desc('no_rkm_medis')
              ->offset($_offset)
              ->limit($perpage)
              ->toArray();
            $jumlah_data = count($totalRecords);
            $jml_halaman = ceil($jumlah_data / $offset);
            $halaman = $_POST['halaman'];
          } else {
            $_offset = (($_POST['halaman'] - 1) * $perpage);
            $rows = $this->db('pasien')
              ->desc('no_rkm_medis')
              ->offset($_offset)
              ->limit($perpage)
              ->toArray();
              $halaman = $_POST['halaman'];
          }
        }else{
          $rows = $this->db('pasien')
            ->desc('no_rkm_medis')
            ->offset(0)
            ->limit($perpage)
            ->toArray();
        }

        $pasien = [];
        foreach ($rows as $row) {
          $row['cekbynokartu'] = url([ADMIN, 'pasien', 'vclaim_bynokartu', $row['no_peserta'], date('Y-m-d')]);
          $row['cekbynik'] = url([ADMIN, 'pasien', 'vclaim_bynik', $row['no_ktp'], date('Y-m-d')]);
          $pasien[] = $row;
        }

        $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
        $cek_pcare = $this->db('mlite_modules')->where('dir', 'pcare')->oneArray();

        echo $this->draw('display.html', [
          'pasien' => $pasien,
          'halaman' => $halaman,
          'jumlah_data' => $jumlah_data,
          'jml_halaman' => $jml_halaman,
          'cek_vclaim' => $cek_vclaim,
          'cek_pcare' => $cek_pcare, 
          'offset' => $offset,
          'admin_mode' => $this->settings->get('settings.admin_mode')
        ]);

        exit();
    }

    public function anyForm()
    {
      $cek_pcare = $this->db('mlite_modules')->where('dir', 'pcare')->oneArray();
      $usernamePcare = '';
      if($cek_pcare) {
        $usernamePcare = $this->settings('pcare', 'usernamePcare');
      }
      $penjab = $this->db('penjab')->where('status', '1')->toArray();
      $stts_nikah = array('BELUM MENIKAH','MENIKAH','JANDA','DUDHA','JOMBLO');
      $agama = array('ISLAM', 'KRISTEN', 'PROTESTAN', 'HINDU', 'BUDHA', 'KONGHUCU', 'KEPERCAYAAN');
      $pnd = array('TS','TK','SD','SMP','SMA','SLTA/SEDERAJAT','D1','D2','D3','D4','S1','S2','S3','-');
      $keluarga = array('AYAH','IBU','ISTRI','SUAMI','SAUDARA','ANAK');
      if (isset($_POST['no_rkm_medis'])){
        $pasien = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();
        $pasien['propinsi'] = $this->db('propinsi')->where('kd_prop', $pasien['kd_prop'])->oneArray();
        $pasien['kabupaten'] = $this->db('kabupaten')->where('kd_kab', $pasien['kd_kab'])->oneArray();
        $pasien['kecamatan'] = $this->db('kecamatan')->where('kd_kec', $pasien['kd_kec'])->oneArray();
        $pasien['kelurahan'] = $this->db('kelurahan')->where('kd_kel', $pasien['kd_kel'])->oneArray();
        echo $this->draw('form.html', [
          'pasien' => $pasien,
          'penjab' => $penjab,
          'stts_nikah' => $stts_nikah,
          'agama' => $agama,
          'pnd' => $pnd,
          'keluarga' => $keluarga,
          'no_rkm_medis_baru' => $this->core->setNoRM(),
          'waapitoken' => $this->settings->get('wagateway.token'),
          'waapiphonenumber' => $this->settings->get('wagateway.phonenumber'),
          'admin_mode' => $this->settings->get('settings.admin_mode'),
          'urlUploadPhoto' => url([ADMIN,'pasien','uploadphoto',$_POST['no_rkm_medis']]),
          'cek_pcare' => $cek_pcare,
          'usernamePcare' => $usernamePcare
        ]);
      } else {
        $pasien = [
          'no_rkm_medis' => '',
          'nm_pasien' => '',
          'no_ktp' => '',
          'jk' => '',
          'tmp_lahir' => '',
          'tgl_lahir' => '',
          'nm_ibu' => '-',
          'alamat' => '',
          'gol_darah' => '-',
          'pekerjaan' => '-',
          'stts_nikah' => '',
          'agama' => 'ISLAM',
          'tgl_daftar' => date('Y-m-d'),
          'no_tlp' => '',
          'umur' => '',
          'pnd' => '-',
          'keluarga' => '',
          'namakeluarga' => '-',
          'kd_pj' => '',
          'no_peserta' => '',
          'kd_kel' => '1',
          'kd_kec' => '1',
          'kd_kab' => '1',
          'pekerjaanpj' => '',
          'alamatpj' => '',
          'kelurahanpj' => '',
          'kecamatanpj' => '',
          'kabupatenpj' => '',
          'perusahaan_pasien' => '',
          'suku_bangsa' => '',
          'bahasa_pasien' => '',
          'cacat_fisik' => '',
          'email' => '-',
          'nip' => '',
          'kd_prop' => '1',
          'propinsipj' => '',
          'propinsi' => ['nm_prop' => '-'],
          'kabupaten' => ['nm_kab' => '-'],
          'kecamatan' => ['nm_kec' => '-'],
          'kelurahan' => ['nm_kel' => '-']
        ];
        echo $this->draw('form.html', [
          'pasien' => $pasien,
          'penjab' => $penjab,
          'stts_nikah' => $stts_nikah,
          'agama' => $agama,
          'pnd' => $pnd,
          'keluarga' => $keluarga,
          'no_rkm_medis_baru' => $this->core->setNoRM(),
          'waapitoken' => $this->settings->get('wagateway.token'),
          'waapiphonenumber' => $this->settings->get('wagateway.phonenumber'),
          'admin_mode' => $this->settings->get('settings.admin_mode'),
          'urlUploadPhoto' => '',
          'cek_pcare' => $cek_pcare,
          'usernamePcare' => $usernamePcare
        ]);
      }
      exit();
    }

    public function postMaxid()
    {
      echo $this->core->setNoRM();
      exit();
    }

    public function postSave()
    {
      $_POST['tgl_daftar'] = date('Y-m-d H:i', strtotime($_POST['tgl_daftar']));
      $pasien = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();
      $cek_prop = $this->db('propinsi')->where('kd_prop', $_POST['kd_prop'])->oneArray();
      if(!$cek_prop){
        $this->db('propinsi')->save(['kd_prop' => $_POST['kd_prop'], 'nm_prop' => $_POST['nm_prop']]);
      }
      $cek_kab = $this->db('kabupaten')->where('kd_kab', $_POST['kd_kab'])->oneArray();
      if(!$cek_kab){
        $this->db('kabupaten')->save(['kd_kab' => $_POST['kd_kab'], 'nm_kab' => $_POST['nm_kab']]);
      }
      $cek_kec = $this->db('kecamatan')->where('kd_kec', $_POST['kd_kec'])->oneArray();
      if(!$cek_kec){
        $this->db('kecamatan')->save(['kd_kec' => $_POST['kd_kec'], 'nm_kec' => $_POST['nm_kec']]);
      }
      $cek_kel = $this->db('kelurahan')->where('nm_kel', $_POST['nm_kel'])->oneArray();
      if(!$cek_kel){
        $result = $this->db('kelurahan')->select('kd_kel')->desc('kd_kel')->limit(1)->oneArray();
        $_POST['kd_kel'] = $result['kd_kel'] + 1;
        $this->db('kelurahan')->save(['kd_kel' => $_POST['kd_kel'], 'nm_kel' => $_POST['nm_kel']]);
      }

      $manual = $_POST['manual'];
      unset($_POST['manual']);

      if (!$pasien) {
        if($manual == '0') {
          $_POST['no_rkm_medis'] = $this->core->setNoRM();
        }
        $_POST['tmp_lahir'] = '-';
        $_POST['umur'] = $this->hitungUmur($_POST['tgl_lahir']);
        $_POST['pekerjaanpj'] = '-';
        $_POST['alamatpj'] = $_POST['alamat'];
        $_POST['kelurahanpj'] = $_POST['nm_kel'];
        $_POST['kecamatanpj'] = $_POST['nm_kec'];
        $_POST['kabupatenpj'] = $_POST['nm_kab'];
        $_POST['perusahaan_pasien'] = '-';
        $_POST['suku_bangsa'] = '1';
        $_POST['bahasa_pasien'] = '1';
        $_POST['cacat_fisik'] = '1';
        $_POST['nip'] = '-';
        $_POST['propinsipj'] = $_POST['nm_prop'];
        unset($_POST['nm_prop']);
        unset($_POST['nm_kab']);
        unset($_POST['nm_kec']);
        unset($_POST['nm_kel']);
        $query = $this->db('pasien')->save($_POST);

        if($query) {
          if($manual == '0') {
            $this->db()->pdo()->exec("UPDATE set_no_rkm_medis SET no_rkm_medis='$_POST[no_rkm_medis]'");
          }
          $data['status'] = 'success';
          echo json_encode($data);
        } else {
          $data['status'] = 'error';
          $data['msg'] = $query->errorInfo()['2'];
          echo json_encode($data);
        }
  
      } else {
        unset($_POST['nm_prop']);
        unset($_POST['nm_kab']);
        unset($_POST['nm_kec']);
        unset($_POST['nm_kel']);
        $_POST['umur'] = $this->hitungUmur($_POST['tgl_lahir']);
        $query = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->update($_POST);

        if($query) {
          $data['status'] = 'success';
          echo json_encode($data);
        } else {
          $data['status'] = 'error';
          $data['msg'] = $query->errorInfo()['2'];
          echo json_encode($data);
        }
  
      }

      exit();
    }

    public function getUploadPhoto()
    {
      $no_rkm_medis = parseURL()[2];
      $this->core->addJS(url(MODULES.'/dashboard/js/admin/webcam.js?v={$mlite.version}'));
      return $this->draw('uploadphoto.html', ['no_rkm_medis' => $no_rkm_medis]);
    }

    public function postSavePhoto($no_rkm_medis = null)
    {

      /*if($_FILES['file']['name'] != ''){
          $test = explode('.', $_FILES['file']['name']);
          $extension = end($test);
          $name = $_POST['no_rkm_medis'].'.'.$extension;

          $location = UPLOADS.'/'.$name;
          move_uploaded_file($_FILES['file']['tmp_name'], $location);

          echo '<img src="'.url().'/uploads/'.$name.'" height="100" width="100" />';
      }*/

        if($no_rkm_medis != null) {
          $_POST['no_rkm_medis'] = $no_rkm_medis;
        }

        $personal_pasien = $this->db('personal_pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();

        if (($photo = isset_or($_FILES['file']['tmp_name'], false)) || !$_POST['no_rkm_medis']) {
            $img = new \Systems\Lib\Image;
            if ($img->load($photo)) {
                if ($img->getInfos('width') < $img->getInfos('height')) {
                    $img->crop(0, 0, $img->getInfos('width'), $img->getInfos('width'));
                } else {
                    $img->crop(0, 0, $img->getInfos('height'), $img->getInfos('height'));
                }

                if ($img->getInfos('width') > 512) {
                    $img->resize(512, 512);
                }

                $gambar = "pages/upload/".uniqid('photo').".".$img->getInfos('type');
                //$gambar = "pages/upload/".$_POST['no_rkm_medis'].".".$img->getInfos('type');
            }

        }

        //if (($photo = isset_or($_FILES['webcam']['tmp_name'], false)) || !$_POST['no_rkm_medis']) {
        if ($photo = isset_or($_FILES['webcam']['tmp_name'], false)) {
            $img = new \Systems\Lib\Image;
            if ($img->load($photo)) {
                if ($img->getInfos('width') < $img->getInfos('height')) {
                    $img->crop(0, 0, $img->getInfos('width'), $img->getInfos('width'));
                } else {
                    $img->crop(0, 0, $img->getInfos('height'), $img->getInfos('height'));
                }

                if ($img->getInfos('width') > 512) {
                    $img->resize(512, 512);
                }

                $gambar = "pages/upload/".uniqid('photo').".".$img->getInfos('type');
                //$gambar = "pages/upload/".$_POST['no_rkm_medis'].".".$img->getInfos('type');
            }

        }

        if ($personal_pasien == 0) {
          $this->db()->pdo()->exec("INSERT INTO `personal_pasien` (`no_rkm_medis`, `gambar`, `password`) VALUES ('{$_POST['no_rkm_medis']}', '$gambar', AES_ENCRYPT('{$_POST['no_rkm_medis']}','windi'))");
        } else{
          $this->db('personal_pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->update(['gambar' => $gambar]);
        }

        if (isset($img) && $img->getInfos('width')) {
            if ($personal_pasien) {
                unlink(WEBAPPS_PATH."/photopasien/".$personal_pasien['gambar']);
            }

            $img->save(WEBAPPS_PATH."/photopasien/".$gambar);

            echo '<img src="'.WEBAPPS_URL.'/photopasien/'.$gambar.'" height="100" width="100" />';

        }

        exit();

    }

    public function postHapus()
    {
      $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->delete();
      exit();
    }

    public function getCetakKartu($no_rkm_medis)
    {
      $kartu['settings'] = $this->settings('settings');
      $kartu['pasien'] = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
      $this->tpl->set('kartu', $this->tpl->noParse_array(htmlspecialchars_array($kartu)));
      echo $this->draw('kartu.html');

      $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => [100, 70], 
        'margin_left' => 4,
        'margin_right' => 4,
        'margin_top' => 4,
        'margin_bottom' => 4
      ]);

      $url = url(ADMIN.'/tmp/kartu.html');
      $html = file_get_contents($url);
      $mpdf->WriteHTML($html);

      // Output a PDF file directly to the browser
      $mpdf->Output();
            
      exit();
    }

    public function getFolder($no_rkm_medis, $no_rawat='')
    {
      $this->_addHeaderFiles();
      $pasien = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
      $reg_periksa = $this->db('reg_periksa')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
        ->where('no_rkm_medis', $no_rkm_medis)
        ->toArray();
      $no_rawat_array = [];
      foreach($reg_periksa as $row) {
        $no_rawat_array[] = $row['no_rawat'];
      }
      $berkas_digital_perawatan = $this->db('berkas_digital_perawatan')
        ->join('master_berkas_digital', 'master_berkas_digital.kode = berkas_digital_perawatan.kode')
        ->in('no_rawat', $no_rawat_array)
        ->toArray();
      if($no_rawat) {
        $berkas_digital_perawatan = $this->db('berkas_digital_perawatan')
        ->join('master_berkas_digital', 'master_berkas_digital.kode = berkas_digital_perawatan.kode')
        ->where('no_rawat', revertNoRawat($no_rawat))
        ->toArray();
      }
      $master_berkas_digital = $this->db('master_berkas_digital')->toArray();
      return $this->draw('folder.html', ['pasien' => $pasien, 'reg_periksa' => $reg_periksa, 'berkas_digital_perawatan' => $berkas_digital_perawatan, 'master_berkas_digital' => $master_berkas_digital]);
    }

    public function postSaveBerkasDigital()
    {

      if(MULTI_APP) {

        $curl = curl_init();
        $filePath = $_FILES['file']['tmp_name'];

        curl_setopt_array($curl, array(
          CURLOPT_URL => str_replace('webapps','',WEBAPPS_URL).'api/berkasdigital',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => array('file'=> new \CURLFILE($filePath),'token' => $this->settings->get('api.berkasdigital_key'), 'no_rawat' => $_POST['no_rawat'], 'kode' => $_POST['kode']),
          CURLOPT_HTTPHEADER => array(),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $json = json_decode($response, true);
        if($json['status'] == 'Success') {
          echo '<br><img src="'.WEBAPPS_URL.'/berkasrawat/'.$json['msg'].'" width="150" />';
        } else {
          echo 'Gagal menambahkan gambar';
        }

      } else {      
        $dir    = $this->_uploads;
        $cntr   = 0;

        $id = convertNorawat($_POST['no_rawat']);
        $temp = explode(".", $_FILES["file"]["name"]);
        $imgName = time().$cntr++;
        $lokasi_file = 'pages/upload/'.$id.'_'.$imgName.'.'.end($temp);
        $FileName = $id.'_'.$imgName.'.'.end($temp);
        $tmpFileName = $_FILES['file']['tmp_name'];
        $uploaded = move_uploaded_file($tmpFileName, $dir.'/'.$FileName);
        if($uploaded) {
            $query = $this->db('berkas_digital_perawatan')->save(['no_rawat' => $_POST['no_rawat'], 'kode' => $_POST['kode'], 'lokasi_file' => $lokasi_file]);
            if($query) {
              echo '<br><img src="'.WEBAPPS_URL.'/berkasrawat/'.$lokasi_file.'" width="150" />';
            }          
        } else {
          echo 'Upload gagal';
        }
      }
      exit();

    }

    public function postHapusBerkasDigital()
    {
      if (file_exists(UPLOADS.'/berkasrawat/'.$_POST['lokasi_file'])) {
        $hapus = unlink(UPLOADS.'/berkasrawat/'.$_POST['lokasi_file']);
        if($hapus) {
          $this->db('berkas_digital_perawatan')->where('lokasi_file', $_POST['lokasi_file'])->delete();
        }
      }
      exit();
    }

    public function getDownloadBerkasDigital()
    {
      $file = explode('/', $_GET['lokasi_file']);
      $file_name = $file['2'];
      $file_url = WEBAPPS_URL.'/berkasrawat/' . $_GET['lokasi_file'];
      
      // Configure.
      header('Content-Type: application/octet-stream');
      header("Content-Transfer-Encoding: Binary"); 
      header("Content-disposition: attachment; filename=\"".$file_name."\"");
      
      // Actual download.
      readfile($file_url);

      exit();
    }

    public function getVclaim_ByNoKartu($noKartu, $tglPelayananSEP)
    {
      $url = url([ADMIN, 'vclaim', 'bynokartu', $noKartu, $tglPelayananSEP]);
      echo $this->draw('vclaim.bynokartu.html', ['url' => $url]);
      exit();
    }

    public function getVclaim_ByNIK($nik, $tglPelayananSEP)
    {
      $url = url([ADMIN, 'vclaim', 'bynik', $nik, $tglPelayananSEP]);
      echo $this->draw('vclaim.bynik.html', ['url' => $url]);
      exit();
    }

    public function getPcare_ByNoKartu($noKartu)
    {
      $url = url([ADMIN, 'pcare', 'byjeniskartu', 'noka', $noKartu]);
      echo $this->draw('pcare.bynokartu.html', ['url' => $url]);
      exit();
    }

    public function getPcare_ByNIK($nik)
    {
      $url = url([ADMIN, 'pcare', 'byjeniskartu', 'nik', $nik]);
      echo $this->draw('pcare.bynik.html', ['url' => $url]);
      exit();
    }

    public function getRiwayatPerawatanXXX($no_rkm_medis)
    {
      $reg_periksa = $this->db('reg_periksa')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
        ->where('no_rkm_medis', $no_rkm_medis)
        ->desc('tgl_registrasi')
        ->toArray();
      echo json_encode($reg_periksa, true);
      exit();
    }

    public function getRiwayatPerawatan($no_rkm_medis)
    {
      $riwayat['settings'] = $this->settings('settings');
      $riwayat['pasien'] = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
      $reg_periksa = $this->db('reg_periksa')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
        ->where('no_rkm_medis', $no_rkm_medis)
        ->desc('tgl_registrasi')
        ->toArray();

      $riwayat['reg_periksa'] = [];
      foreach ($reg_periksa as $row) {

        $row['diagnosa_pasien'] = $this->db('diagnosa_pasien')
          ->join('penyakit', 'penyakit.kd_penyakit=diagnosa_pasien.kd_penyakit')
          ->where('no_rawat', $row['no_rawat'])
          ->asc('prioritas')
          ->toArray();
        $row['prosedur_pasien'] = $this->db('prosedur_pasien')
          ->join('icd9', 'icd9.kode=prosedur_pasien.kode')
          ->where('no_rawat', $row['no_rawat'])
          ->asc('prioritas')
          ->toArray();
        $row['pemeriksaan_ralan'] = $this->db('pemeriksaan_ralan')->where('no_rawat', $row['no_rawat'])->toArray();
        $row['rawat_jl_dr'] = $this->db('rawat_jl_dr')
          ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw=rawat_jl_dr.kd_jenis_prw')
          ->join('dokter', 'dokter.kd_dokter=rawat_jl_dr.kd_dokter')
          ->where('no_rawat', $row['no_rawat'])
          ->toArray();
        $row['rawat_jl_pr'] = $this->db('rawat_jl_pr')
          ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw=rawat_jl_pr.kd_jenis_prw')
          ->join('petugas', 'petugas.nip=rawat_jl_pr.nip')
          ->where('no_rawat', $row['no_rawat'])
          ->toArray();
        $rows['rawat_jl_drpr'] = $this->db('rawat_jl_drpr')
          ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw=rawat_jl_drpr.kd_jenis_prw')
          ->where('no_rawat', $row['no_rawat'])
          ->toArray();
        $row['rawat_jl_drpr'] = [];
        foreach ($rows['rawat_jl_drpr'] as $row2) {
          $dokter = $this->db('dokter')->where('kd_dokter', $row2['kd_dokter'])->oneArray();
          $petugas = $this->db('petugas')->where('nip', $row2['nip'])->oneArray();
          $row2['nm_dokter'] = $dokter['nm_dokter'];
          $row2['nama'] = $petugas['nama'];
          $row['rawat_jl_drpr'][] = $row2;
        }
        $row['pemeriksaan_ranap'] = [];
        $row['rawat_inap_dr'] = [];
        $row['rawat_inap_pr'] = [];
        $row['rawat_inap_drpr'] = [];
        $check_table = $this->db()->pdo()->query("SHOW TABLES LIKE 'pemeriksaan_ranap'");
        $check_table->execute();
        $check_table = $check_table->fetch();
        if($check_table) {
          $row['pemeriksaan_ranap'] = $this->db('pemeriksaan_ranap')->where('no_rawat', $row['no_rawat'])->toArray();
          $row['rawat_inap_dr'] = $this->db('rawat_inap_dr')
            ->join('jns_perawatan_inap', 'jns_perawatan_inap.kd_jenis_prw=rawat_inap_dr.kd_jenis_prw')
            ->join('dokter', 'dokter.kd_dokter=rawat_inap_dr.kd_dokter')
            ->where('no_rawat', $row['no_rawat'])
            ->toArray();
          $row['rawat_inap_pr'] = $this->db('rawat_inap_pr')
            ->join('jns_perawatan_inap', 'jns_perawatan_inap.kd_jenis_prw=rawat_inap_pr.kd_jenis_prw')
            ->join('petugas', 'petugas.nip=rawat_inap_pr.nip')
            ->where('no_rawat', $row['no_rawat'])
            ->toArray();
          $rows['rawat_inap_drpr'] = $this->db('rawat_inap_drpr')
            ->join('jns_perawatan_inap', 'jns_perawatan_inap.kd_jenis_prw=rawat_inap_drpr.kd_jenis_prw')
            ->where('no_rawat', $row['no_rawat'])
            ->toArray();
          foreach ($rows['rawat_inap_drpr'] as $row3) {
            $dokter = $this->db('dokter')->where('kd_dokter', $row3['kd_dokter'])->oneArray();
            $petugas = $this->db('petugas')->where('nip', $row3['nip'])->oneArray();
            $row3['nm_dokter'] = $dokter['nm_dokter'];
            $row3['nama'] = $petugas['nama'];
            $row['rawat_inap_drpr'][] = $row3;
          }
        }

        $rows_periksa_lab = $this->db('periksa_lab')
          ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw')
          ->where('no_rawat', $row['no_rawat'])
          ->toArray();

        $row['periksa_lab'] = [];
        foreach ($rows_periksa_lab as $value) {
          $value['detail_periksa_lab'] = $this->db('detail_periksa_lab')
            ->join('template_laboratorium', 'template_laboratorium.id_template=detail_periksa_lab.id_template')
            ->where('detail_periksa_lab.no_rawat', $value['no_rawat'])
            ->where('detail_periksa_lab.kd_jenis_prw', $value['kd_jenis_prw'])
            ->where('tgl_periksa', $value['tgl_periksa'])
            ->where('jam', $value['jam'])
            ->toArray();
          $row['periksa_lab'][] = $value;
        }

        $row['periksa_radiologi'] = [];
        $rows_radiologi = $this->db('periksa_radiologi')
          ->where('periksa_radiologi.no_rawat', $row['no_rawat'])
          ->group('tgl_periksa')
          ->group('jam')
          ->toArray();

        foreach ($rows_radiologi as $value) {
          $value['pemeriksaan_radiologi'] = $this->db('periksa_radiologi')
            ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw=periksa_radiologi.kd_jenis_prw')
            ->where('no_rawat', $value['no_rawat'])
            ->where('tgl_periksa', $value['tgl_periksa'])
            ->where('jam', $value['jam'])
            ->toArray();
          $value['hasil_radiologi'] = $this->db('hasil_radiologi')
            ->where('no_rawat', $value['no_rawat'])
            ->where('tgl_periksa', $value['tgl_periksa'])
            ->where('jam', $value['jam'])
            ->toArray();
          $value['gambar_radiologi'] = $this->db('gambar_radiologi')
            ->where('no_rawat', $value['no_rawat'])
            ->where('tgl_periksa', $value['tgl_periksa'])
            ->where('jam', $value['jam'])
            ->toArray();
          $row['periksa_radiologi'][] = $value;
        }

        $detail_pemberian_obat = $this->db('detail_pemberian_obat')
          ->where('no_rawat', $row['no_rawat'])
          ->group('tgl_perawatan')
          ->group('jam')
          ->toArray();

        $row['pemberian_obat'] = [];
        foreach ($detail_pemberian_obat as $row_pemberian_obat) {
          $row_pemberian_obat['data_pemberian_obat'] = $this->db('detail_pemberian_obat')
            ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
            ->where('detail_pemberian_obat.no_rawat', $row_pemberian_obat['no_rawat'])
            ->where('detail_pemberian_obat.tgl_perawatan', $row_pemberian_obat['tgl_perawatan'])
            ->where('detail_pemberian_obat.jam', $row_pemberian_obat['jam'])
            ->toArray();
          $row['pemberian_obat'][] = $row_pemberian_obat;
        }

        $row['operasi'] = $this->db('operasi')
          ->join('paket_operasi', 'paket_operasi.kode_paket=operasi.kode_paket')
          ->where('no_rawat', $row['no_rawat'])
          ->toArray();

        $row['obat_operasi'] = $this->db('beri_obat_operasi')
          ->join('obatbhp_ok', 'obatbhp_ok.kd_obat=beri_obat_operasi.kd_obat')
          ->where('no_rawat', $row['no_rawat'])
          ->toArray();

        $row['catatan_perawatan'] = $this->db('catatan_perawatan')->where('no_rawat', $row['no_rawat'])->oneArray();
        $row['berkas_digital'] = $this->db('berkas_digital_perawatan')
          ->join('master_berkas_digital', 'master_berkas_digital.kode=berkas_digital_perawatan.kode')
          ->where('no_rawat', $row['no_rawat'])
          ->toArray();

        $row['penilaian_medis_ralan'] = $this->db('mlite_penilaian_medis_ralan')
        ->join('dokter', 'dokter.kd_dokter=mlite_penilaian_medis_ralan.kd_dokter')
        ->where('no_rawat', $row['no_rawat'])
        ->toArray();

        $riwayat['reg_periksa'][] = $row;
      }
      $this->tpl->set('riwayat', $this->tpl->noParse_array(htmlspecialchars_array($riwayat)));
      echo $this->draw('riwayat.perawatan.html');
      exit();
    }

    public function postCetak()
    {
      $this->db()->pdo()->exec("DELETE FROM `mlite_temporary`");
      $cari = $_POST['cari'];
      $this->db()->pdo()->exec("INSERT INTO `mlite_temporary` (
        `temp1`,
        `temp2`,
        `temp3`,
        `temp4`,
        `temp5`,
        `temp6`,
        `temp7`,
        `temp8`,
        `temp9`,
        `temp10`,
        `temp11`,
        `temp12`,
        `temp13`,
        `temp14`,
        `temp15`,
        `temp16`,
        `temp17`,
        `temp18`,
        `temp19`,
        `temp20`,
        `temp21`,
        `temp22`,
        `temp23`,
        `temp24`,
        `temp25`,
        `temp26`,
        `temp27`,
        `temp28`,
        `temp29`,
        `temp30`,
        `temp31`,
        `temp32`,
        `temp33`,
        `temp34`,
        `temp35`,
        `temp36`
      )
      SELECT *
      FROM `pasien`
      WHERE (`no_rkm_medis` LIKE '%$cari%' OR `nm_pasien` LIKE '%$cari%' OR `alamat` LIKE '%$cari%')
      ");

      $cetak = $this->db('mlite_temporary')->toArray();
      return $this->draw('cetak.pasien.html', ['cetak' => $cetak]);
      exit();
    }

    public function anyWilayah()
    {
      $show = isset($_GET['show']) ? $_GET['show'] : "";
      switch($show){
      	default:
        break;
        case "caripropinsi":
          if(isset($_POST["query"])){
            $output = '';
            $key = "%".$_POST["query"]."%";
            $rows = $this->db('propinsi')->like('nm_prop', $key)->asc('kd_prop')->limit(10)->toArray();
            $output = '';
            if(count($rows)){
              foreach ($rows as $row) {
                $output .= '<li class="list-group-item link-class">'.$row["kd_prop"].': '.$row["nm_prop"].'</li>';
              }
            }
            echo $output;
          }
        break;
        case "carikabupaten":
          if(isset($_POST["query"])){
            $output = '';
            $key = "%".$_POST["query"]."%";
            $rows = $this->db('kabupaten')->like('nm_kab', $key)->asc('kd_kab')->limit(10)->toArray();
            $output = '';
            if(count($rows)){
              foreach ($rows as $row) {
                $output .= '<li class="list-group-item link-class">'.$row["kd_kab"].': '.$row["nm_kab"].'</li>';
              }
            }
            echo $output;
          }
        break;
        case "carikecamatan":
          if(isset($_POST["query"])){
            $output = '';
            $key = "%".$_POST["query"]."%";
            $rows = $this->db('kecamatan')->like('nm_kec', $key)->asc('kd_kec')->limit(10)->toArray();
            $output = '';
            if(count($rows)){
              foreach ($rows as $row) {
                $output .= '<li class="list-group-item link-class">'.$row["kd_kec"].': '.$row["nm_kec"].'</li>';
              }
            }
            echo $output;
          }
        break;
        case "carikelurahan":
          if(isset($_POST["query"])){
            $output = '';
            $key = "%".$_POST["query"]."%";
            $rows = $this->db('kelurahan')->like('nm_kel', $key)->asc('kd_kel')->limit(10)->toArray();
            $output = '';
            if(count($rows)){
              foreach ($rows as $row) {
                $output .= '<li class="list-group-item link-class">'.$row["kd_kel"].': '.$row["nm_kel"].'</li>';
              }
            }
            echo $output;
          }
        break;
      }
      exit();
    }

    public function hitungUmur($tanggal_lahir)
    {
      	$birthDate = new \DateTime($tanggal_lahir);
      	$today = new \DateTime("today");
      	$umur = "0 Th 0 Bl 0 Hr";
        if ($birthDate < $today) {
        	$y = $today->diff($birthDate)->y;
        	$m = $today->diff($birthDate)->m;
        	$d = $today->diff($birthDate)->d;
          $umur =  $y." Th ".$m." Bl ".$d." Hr";
        }
      	return $umur;
    }

    public function getExportPDF()
    {
      $query = $_GET['query'];
      $tgl_awal = $_GET['tgl_awal'];
      $tgl_akhir = $_GET['tgl_akhir'];
      $filter = $_GET['filter'];

      $sql = "SELECT * FROM pasien";
        if(isset($_GET['tgl_awal']) && isset($_GET['tgl_akhir']) && $_GET['tgl_awal'] !='' && $_GET['tgl_akhir'] !='') {
          $sql .=" WHERE tgl_daftar BETWEEN '$tgl_awal' AND '$tgl_akhir'";
        }
        if(isset($_GET['query']) && $_GET['query'] !='') {
          $sql .=" AND nm_pasien LIKE '%$query%'";
        }
        if(isset($_GET['filter']) && $_GET['filter'] !='') {
          $sql .=" AND kd_pj = '$filter'";
        }
      $stmt = $this->db()->pdo()->prepare($sql);
      $stmt->execute();
      $rows = $stmt->fetchAll();        

      echo $this->draw('pasien.export.pdf.html', ['pasien' => $rows]);

      $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'orientation' => 'L'
      ]);

      $mpdf->SetHTMLHeader($this->core->setPrintHeader());
      $mpdf->SetHTMLFooter($this->core->setPrintFooter());
            
      $url = url(ADMIN.'/tmp/pasien.export.pdf.html');
      $html = file_get_contents($url);
      $mpdf->WriteHTML($this->core->setPrintCss(),\Mpdf\HTMLParserMode::HEADER_CSS);
      $mpdf->WriteHTML($html,\Mpdf\HTMLParserMode::HTML_BODY);

      // Output a PDF file directly to the browser
      $mpdf->Output();
      exit();
    }

    public function getExportXLS()
    {
      echo "Cetak XLS";
      exit();
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        $this->assign['websocket'] = $this->settings->get('settings.websocket');
        $this->assign['websocket_proxy'] = $this->settings->get('settings.websocket_proxy');
        echo $this->draw(MODULES.'/pasien/js/admin/pasien.js', ['mlite' => $this->assign]);
        exit();
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
        $this->core->addJS(url('assets/jscripts/lightbox/lightbox.min.js'));
        $this->core->addCSS(url('assets/jscripts/lightbox/lightbox.min.css'));
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));
        $this->core->addJS(url([ADMIN, 'pasien', 'javascript']), 'footer');
    }

    public function getSettings()
    {
      $set_no_rkm_medis = $this->db('set_no_rkm_medis')->oneArray();
      return $this->draw('settings.html', ['set_no_rkm_medis' => $set_no_rkm_medis]);
    }

    public function postSaveSettings()
    {
        $this->db()->pdo()->exec("DELETE FROM `set_no_rkm_medis`");
        $set_no_rkm_medis = $this->db('set_no_rkm_medis')->save(['no_rkm_medis' => $_POST['set_no_rkm_medis']]);
        if($set_no_rkm_medis) {
          $this->notify('success', 'Pengaturan telah disimpan');
        } else {
          $this->notify('error', 'Pengaturan gagal disimpan');
        }
        redirect(url([ADMIN, 'pasien', 'settings']));
    }

    public function getCetakMpdf()
    {
      $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'orientation' => 'L'
      ]);

      $mpdf->SetHTMLHeader($this->core->setPrintHeader());
      $mpdf->SetHTMLFooter($this->core->setPrintFooter());
            
      $url = url(ADMIN.'/tmp/cetak.pasien.html');
      $html = file_get_contents($url);
      $mpdf->WriteHTML($this->core->setPrintCss(),\Mpdf\HTMLParserMode::HEADER_CSS);
      $mpdf->WriteHTML($html,\Mpdf\HTMLParserMode::HTML_BODY);

      // Output a PDF file directly to the browser
      $mpdf->Output();
      exit();      
    }

    public function getCetakRiwayatMpdf()
    {
      $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'orientation' => 'L'
      ]);

      $css = '
      <style>
        del { 
          display: none;
        }
        table {
          padding-top: 1cm;
          padding-bottom: 1cm;
          font-size: 10px;
        }
        td, th {
          border-bottom: 1px solid #dddddd;
          padding: 5px;
        }        
        tr:nth-child(even) {
          background-color: #ffffff;
        }
      </style>
      ';

      // $mpdf->SetHTMLHeader($this->core->setPrintHeader());
      // $mpdf->SetHTMLFooter($this->core->setPrintFooter());
            
      $url = url(ADMIN.'/tmp/riwayat.perawatan.html');
      $html = file_get_contents($url);
      $mpdf->WriteHTML($this->core->setPrintCss(),\Mpdf\HTMLParserMode::HEADER_CSS);
      $mpdf->WriteHTML($css);
      $mpdf->WriteHTML($html);

      // Output a PDF file directly to the browser
      $mpdf->Output();
      exit();      
    }    

    public function getExcel()
    {
      $file = "data.pasien.xls";
      $html = file_get_contents(url(ADMIN.'/tmp/cetak.pasien.html'));
      header("Content-type: application/vnd-ms-excel");
      header("Content-Disposition: attachment; filename=$file");
      echo "<!DOCTYPE html><html><head></head><body>";
      echo $html;
      echo "</body></html>";
      exit();
    }

    public function postData()
    {
        $draw = $_POST['draw'] ?? 0;
        $row1 = $_POST['start'] ?? 0;
        $rowperpage = $_POST['length'] ?? 10;
        $columnIndex = $_POST['order'][0]['column'] ?? 0;
        $columnName = $_POST['columns'][$columnIndex]['data'] ?? 'no_rkm_medis';
        $columnSortOrder = $_POST['order'][0]['dir'] ?? 'asc';
        $searchValue = $_POST['search']['value'] ?? '';
    
        $search_field_pasien = $_POST['search_field_pasien'] ?? 'pasien.nm_pasien';
        $search_text = $_POST['search_text_pasien'] ?? '';

        $tgl_dari = $_POST['tgl_dari'] ?? '';
        $tgl_sampai = $_POST['tgl_sampai'] ?? '';
        
    
        $searchQuery = "";
    
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $searchQuery .= " AND pasien.tgl_daftar BETWEEN :tgl_dari AND :tgl_sampai ";
        } elseif (!empty($tgl_dari)) {
            $searchQuery .= " AND pasien.tgl_daftar >= :tgl_dari ";
        } elseif (!empty($tgl_sampai)) {
            $searchQuery .= " AND pasien.tgl_daftar <= :tgl_sampai ";
        }
        if (!empty($search_text)) {
            $searchQuery .= " AND ($search_field_pasien LIKE :search_text)";
        }
    
        $pdo = $this->db()->pdo();
    
        // Hitung total record tanpa filter
        $sel = $pdo->prepare("SELECT COUNT(*) AS allcount FROM pasien");
        $sel->execute();
        $records = $sel->fetch();
        $totalRecords = $records['allcount'];
    
        // Hitung total record dengan filter
        $sqlCount = "
            SELECT COUNT(*) AS allcount 
            FROM pasien
            LEFT JOIN penjab ON pasien.kd_pj = penjab.kd_pj
            LEFT JOIN propinsi ON pasien.kd_prop = propinsi.kd_prop
            LEFT JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
            LEFT JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
            LEFT JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
            WHERE 1=1 $searchQuery
        ";
        $sel = $pdo->prepare($sqlCount);
        if (!empty($search_text)) {
            $sel->bindValue(':search_text', "%{$search_text}%");
        }
        if (!empty($tgl_dari)) {
            $sel->bindValue(':tgl_dari', $tgl_dari);
        }
        if (!empty($tgl_sampai)) {
            $sel->bindValue(':tgl_sampai', $tgl_sampai);
        }
        $sel->execute();
        $records = $sel->fetch();
        $totalRecordwithFilter = $records['allcount'];
    
        // Query data
        $sql = "
            SELECT 
                pasien.*, 
                penjab.png_jawab AS nama_penjab,
                propinsi.nm_prop AS nama_propinsi,
                kabupaten.nm_kab AS nama_kabupaten,
                kecamatan.nm_kec AS nama_kecamatan,
                kelurahan.nm_kel AS nama_kelurahan
            FROM pasien
            LEFT JOIN penjab ON pasien.kd_pj = penjab.kd_pj
            LEFT JOIN propinsi ON pasien.kd_prop = propinsi.kd_prop
            LEFT JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
            LEFT JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
            LEFT JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
            WHERE 1=1 $searchQuery 
            ORDER BY $columnName $columnSortOrder 
            LIMIT $row1, $rowperpage
        ";
    
        $sel = $pdo->prepare($sql);
        if (!empty($search_text)) {
            $sel->bindValue(':search_text', "%{$search_text}%");
        }
        if (!empty($tgl_dari)) {
            $sel->bindValue(':tgl_dari', $tgl_dari);
        }
        if (!empty($tgl_sampai)) {
            $sel->bindValue(':tgl_sampai', $tgl_sampai);
        }
        $sel->execute();
        $result = $sel->fetchAll(\PDO::FETCH_ASSOC);

        $data = array();
        foreach ($result as $row) {
            $data[] = $row;
        }
    
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );
    
        echo json_encode($response);
        exit();
    }
    
    public function postAksi()
    {
        $act = $_POST['typeact'] ?? '';

        if (!in_array($act, ['add', 'edit', 'del', 'lihat'])) {
            echo json_encode(["status" => "error", "message" => "Aksi tidak dikenali."]);
            exit();
        }

        try {
            if ($act == 'add') {

                if($this->core->loadDisabledMenu('pasien')['can_create'] == 'true') {
                  http_response_code(403);
                  $data = array(
                    'code' => '403', 
                    'status' => 'error', 
                    'msg' => 'Maaf, akses dibatasi!'
                  );
                  echo json_encode($data);    
                  exit();
                }

                $no_rkm_medis = $_POST['no_rkm_medis'];
                $nm_pasien = $_POST['nm_pasien'];
                $no_ktp = $_POST['no_ktp'];
                $jk = $_POST['jk'];
                $tmp_lahir = $_POST['tmp_lahir'];
                $tgl_lahir = $_POST['tgl_lahir'];
                $nm_ibu = $_POST['nm_ibu'];
                $alamat = $_POST['alamat'];
                $gol_darah = $_POST['gol_darah'];
                $pekerjaan = $_POST['pekerjaan'];
                $stts_nikah = $_POST['stts_nikah'];
                $agama = $_POST['agama'];
                $tgl_daftar = $_POST['tgl_daftar'];
                $no_tlp = $_POST['no_tlp'];
                $umur = $_POST['umur'];
                $pnd = $_POST['pnd'];
                $keluarga = $_POST['keluarga'];
                $namakeluarga = $_POST['namakeluarga'];
                $kd_pj = $_POST['kd_pj'];
                $no_peserta = $_POST['no_peserta'];
                $kd_kel = $_POST['kd_kel'];
                $kd_kec = $_POST['kd_kec'];
                $kd_kab = $_POST['kd_kab'];
                $pekerjaanpj = $_POST['pekerjaanpj'];
                $alamatpj = $_POST['alamatpj'];
                $kelurahanpj = $_POST['kelurahanpj'];
                $kecamatanpj = $_POST['kecamatanpj'];
                $kabupatenpj = $_POST['kabupatenpj'];
                $perusahaan_pasien = $_POST['perusahaan_pasien'];
                $suku_bangsa = $_POST['suku_bangsa'];
                $bahasa_pasien = $_POST['bahasa_pasien'];
                $cacat_fisik = $_POST['cacat_fisik'];
                $email = $_POST['email'];
                $nip = $_POST['nip'];
                $kd_prop = $_POST['kd_prop'];
                $propinsipj = $_POST['propinsipj'];


                $sql = "INSERT INTO pasien VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $binds = [$no_rkm_medis, $nm_pasien, $no_ktp, $jk, $tmp_lahir, $tgl_lahir, $nm_ibu, $alamat, $gol_darah, $pekerjaan, $stts_nikah, $agama, $tgl_daftar, $no_tlp, $umur, $pnd, $keluarga, $namakeluarga, $kd_pj, $no_peserta, $kd_kel, $kd_kec, $kd_kab, $pekerjaanpj, $alamatpj, $kelurahanpj, $kecamatanpj, $kabupatenpj, $perusahaan_pasien, $suku_bangsa, $bahasa_pasien, $cacat_fisik, $email, $nip, $kd_prop, $propinsipj];
                $stmt = $this->db()->pdo()->prepare($sql);
                $stmt->execute($binds);

                if($this->settings->get('settings.log_query') == 'ya') {
                  \Systems\Lib\QueryWrapper::logPdoQuery($sql, $binds);
                }

                http_response_code(200);
                echo json_encode([
                  "code" => "200", 
                  "status" => "success", 
                  "message" => "Data berhasil ditambahkan."
                ]);

            } elseif ($act == 'edit') {

                if($this->core->loadDisabledMenu('pasien')['can_update'] == 'true') {
                  http_response_code(403);
                  $data = array(
                    'code' => '403', 
                    'status' => 'error', 
                    'msg' => 'Maaf, akses dibatasi!'
                  );
                  echo json_encode($data);    
                  exit();
                }

                $no_rkm_medis = $_POST['no_rkm_medis'];
                $nm_pasien = $_POST['nm_pasien'];
                $no_ktp = $_POST['no_ktp'];
                $jk = $_POST['jk'];
                $tmp_lahir = $_POST['tmp_lahir'];
                $tgl_lahir = $_POST['tgl_lahir'];
                $nm_ibu = $_POST['nm_ibu'];
                $alamat = $_POST['alamat'];
                $gol_darah = $_POST['gol_darah'];
                $pekerjaan = $_POST['pekerjaan'];
                $stts_nikah = $_POST['stts_nikah'];
                $agama = $_POST['agama'];
                $tgl_daftar = $_POST['tgl_daftar'];
                $no_tlp = $_POST['no_tlp'];
                $umur = $_POST['umur'];
                $pnd = $_POST['pnd'];
                $keluarga = $_POST['keluarga'];
                $namakeluarga = $_POST['namakeluarga'];
                $kd_pj = $_POST['kd_pj'];
                $no_peserta = $_POST['no_peserta'];
                $kd_kel = $_POST['kd_kel'];
                $kd_kec = $_POST['kd_kec'];
                $kd_kab = $_POST['kd_kab'];
                $pekerjaanpj = $_POST['pekerjaanpj'];
                $alamatpj = $_POST['alamatpj'];
                $kelurahanpj = $_POST['kelurahanpj'];
                $kecamatanpj = $_POST['kecamatanpj'];
                $kabupatenpj = $_POST['kabupatenpj'];
                $perusahaan_pasien = $_POST['perusahaan_pasien'];
                $suku_bangsa = $_POST['suku_bangsa'];
                $bahasa_pasien = $_POST['bahasa_pasien'];
                $cacat_fisik = $_POST['cacat_fisik'];
                $email = $_POST['email'];
                $nip = $_POST['nip'];
                $kd_prop = $_POST['kd_prop'];
                $propinsipj = $_POST['propinsipj'];

                $sql = "UPDATE pasien SET no_rkm_medis=?, nm_pasien=?, no_ktp=?, jk=?, tmp_lahir=?, tgl_lahir=?, nm_ibu=?, alamat=?, gol_darah=?, pekerjaan=?, stts_nikah=?, agama=?, tgl_daftar=?, no_tlp=?, umur=?, pnd=?, keluarga=?, namakeluarga=?, kd_pj=?, no_peserta=?, kd_kel=?, kd_kec=?, kd_kab=?, pekerjaanpj=?, alamatpj=?, kelurahanpj=?, kecamatanpj=?, kabupatenpj=?, perusahaan_pasien=?, suku_bangsa=?, bahasa_pasien=?, cacat_fisik=?, email=?, nip=?, kd_prop=?, propinsipj=? WHERE no_rkm_medis=?";
                $binds = [$no_rkm_medis, $nm_pasien, $no_ktp, $jk, $tmp_lahir, $tgl_lahir, $nm_ibu, $alamat, $gol_darah, $pekerjaan, $stts_nikah, $agama, $tgl_daftar, $no_tlp, $umur, $pnd, $keluarga, $namakeluarga, $kd_pj, $no_peserta, $kd_kel, $kd_kec, $kd_kab, $pekerjaanpj, $alamatpj, $kelurahanpj, $kecamatanpj, $kabupatenpj, $perusahaan_pasien, $suku_bangsa, $bahasa_pasien, $cacat_fisik, $email, $nip, $kd_prop, $propinsipj,$no_rkm_medis];
                $stmt = $this->db()->pdo()->prepare($sql);
                $stmt->execute($binds);

                if($this->settings->get('settings.log_query') == 'ya') {
                  \Systems\Lib\QueryWrapper::logPdoQuery($sql, $binds);
                }

                http_response_code(200);
                echo json_encode([
                  "code" => "200", 
                  "status" => "success", 
                  "message" => "Data berhasil diperbarui."
                ]);

            } elseif ($act == 'del') {

                if($this->core->loadDisabledMenu('pasien')['can_delete'] == 'true') {
                  http_response_code(403);
                  $data = array(
                    'code' => '403', 
                    'status' => 'error', 
                    'msg' => 'Maaf, akses dibatasi!'
                  );
                  echo json_encode($data);    
                  exit();
                }

                $no_rkm_medis= $_POST['no_rkm_medis'];

                $sql = "DELETE FROM pasien WHERE no_rkm_medis='$no_rkm_medis'";
                $binds = [];

                $stmt = $this->db()->pdo()->prepare($sql);
                $stmt->execute();

                if($this->settings->get('settings.log_query') == 'ya') {
                  \Systems\Lib\QueryWrapper::logPdoQuery($sql, $binds);
                }

                if ($stmt->rowCount() > 0) {
                    echo json_encode(["status" => "success", "message" => "Data berhasil dihapus."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Data tidak ditemukan atau gagal dihapus."]);
                }

            } elseif ($act == 'lihat') {

                if($this->core->loadDisabledMenu('pasien')['can_read'] == 'true') {
                  http_response_code(403);
                  $data = array(
                    'code' => '403', 
                    'status' => 'error', 
                    'msg' => 'Maaf, akses dibatasi!'
                  );
                  echo json_encode($data);    
                  exit();
                }

                $search_field_pasien = $_POST['search_field_pasien'];
                $search_text  = $_POST['search_text_pasien'];
                $tgl_dari = $_POST['tgl_dari'] ?? '';
                $tgl_sampai = $_POST['tgl_sampai'] ?? '';
                
                $searchQuery = "";
                if (!empty($tgl_dari) && !empty($tgl_sampai)) {
                    $searchQuery .= " AND tgl_daftar BETWEEN :tgl_dari AND :tgl_sampai ";
                }          
                if ($search_text != '') {
                    // Pastikan field dicari dari alias tabel yang sesuai
                    $searchQuery .= " AND ($search_field_pasien LIKE :search_text_pasien)";
                }
                
                
                $sql = "
                    SELECT 
                        pasien.*,
                        penjab.png_jawab AS nama_penjab,
                        propinsi.nm_prop AS nama_propinsi,
                        kabupaten.nm_kab AS nama_kabupaten,
                        kecamatan.nm_kec AS nama_kecamatan,
                        kelurahan.nm_kel AS nama_kelurahan
                    FROM pasien
                    LEFT JOIN penjab ON pasien.kd_pj = penjab.kd_pj
                    LEFT JOIN propinsi ON pasien.kd_prop = propinsi.kd_prop
                    LEFT JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
                    LEFT JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
                    LEFT JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
                    WHERE 1=1
                    $searchQuery
                ";
                
                $user_lihat = $this->db()->pdo()->prepare($sql);
                
                if (!empty($tgl_dari) && !empty($tgl_sampai)) {
                    $user_lihat->bindValue(':tgl_dari', $tgl_dari);
                    $user_lihat->bindValue(':tgl_sampai', $tgl_sampai);
                }
              
                if ($search_text != '') {
                    $user_lihat->bindValue(':search_text_pasien', '%' . $search_text . '%');
                }
                
                $user_lihat->execute();
                $result = $user_lihat->fetchAll(\PDO::FETCH_ASSOC);
                
                $data = array();
                foreach ($result as $row) {
                    $data[] = array(
                        'no_rkm_medis'     => $row['no_rkm_medis'],
                        'nm_pasien'        => $row['nm_pasien'],
                        'no_ktp'           => $row['no_ktp'],
                        'jk'               => $row['jk'],
                        'tmp_lahir'        => $row['tmp_lahir'],
                        'tgl_lahir'        => $row['tgl_lahir'],
                        'nm_ibu'           => $row['nm_ibu'],
                        'alamat'           => $row['alamat'],
                        'gol_darah'        => $row['gol_darah'],
                        'pekerjaan'        => $row['pekerjaan'],
                        'stts_nikah'       => $row['stts_nikah'],
                        'agama'            => $row['agama'],
                        'tgl_daftar'       => $row['tgl_daftar'],
                        'no_tlp'           => $row['no_tlp'],
                        'umur'             => $row['umur'],
                        'pnd'              => $row['pnd'],
                        'keluarga'         => $row['keluarga'],
                        'namakeluarga'     => $row['namakeluarga'],
                        'kd_pj'            => $row['kd_pj'],
                        'nama_penjab'      => $row['nama_penjab'],      // dari penjab
                        'no_peserta'       => $row['no_peserta'],
                        'kd_kel'           => $row['kd_kel'],
                        'kd_kec'           => $row['kd_kec'],
                        'kd_kab'           => $row['kd_kab'],
                        'kd_prop'          => $row['kd_prop'],
                        'nama_kelurahan'   => $row['nama_kelurahan'],   // dari kelurahan
                        'nama_kecamatan'   => $row['nama_kecamatan'],   // dari kecamatan
                        'nama_kabupaten'   => $row['nama_kabupaten'],   // dari kabupaten
                        'nama_propinsi'    => $row['nama_propinsi'],    // dari propinsi
                        'pekerjaanpj'      => $row['pekerjaanpj'],
                        'alamatpj'         => $row['alamatpj'],
                        'kelurahanpj'      => $row['kelurahanpj'],
                        'kecamatanpj'      => $row['kecamatanpj'],
                        'kabupatenpj'      => $row['kabupatenpj'],
                        'propinsipj'       => $row['propinsipj'],
                        'perusahaan_pasien'=> $row['perusahaan_pasien'],
                        'suku_bangsa'      => $row['suku_bangsa'],
                        'bahasa_pasien'    => $row['bahasa_pasien'],
                        'cacat_fisik'      => $row['cacat_fisik'],
                        'email'            => $row['email'],
                        'nip'              => $row['nip']
                    );
                }
                
                http_response_code(200);
                echo json_encode($data);
            }
        } catch (\PDOException $e) {
            if($this->settings->get('settings.log_query') == 'ya') {
              if (in_array($act, ['add', 'edit', 'del'])) {
                \Systems\Lib\QueryWrapper::logPdoQuery($sql, $binds, $e->getMessage());   
              } 
            }

            http_response_code(201);
            echo json_encode([
              "code" => "201", 
              "status" => "error", 
              "message" => $e->getMessage()
            ]);
        }

        exit();
    }

    public function getDetail($no_rkm_medis)
    {
        $detail = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->toArray();
        $settings =  $this->settings('settings');
        echo $this->draw('detail.html', ['detail' => $detail, 'settings' => $settings]);
        exit();
    }

    public function getChart($type = '', $column = '')
    {
        if($type == ''){
            $type = 'pie';
        }

        $labels = $this->db('pasien')->select('agama')->group('agama')->toArray();
        $labels = json_encode(array_column($labels, 'agama'));
        $datasets = $this->db('pasien')->select('COUNT(agama)')->group('agama')->toArray();
        $datasets = json_encode(array_column($datasets, 'COUNT(agama)'));

        if(isset_or($column)) {
            $labels = $this->db('pasien')->select(''.$column.'')->group(''.$column.'')->toArray();
            $labels = json_encode(array_column($labels, ''.$column.''));
            $datasets = $this->db('pasien')->select('COUNT('.$column.')')->group(''.$column.'')->toArray();  
            $datasets = json_encode(array_column($datasets, 'COUNT('.$column.')'));
        }

        $database = DBNAME;
        $nama_table = 'pasien';

        $get_table = $this->db()->pdo()->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$database' AND TABLE_NAME='$nama_table'");
        $get_table->execute();
        $result = $get_table->fetchAll();

        echo $this->draw('chart.html', ['type' => $type, 'column' => $result, 'labels' => $labels, 'datasets' => $datasets]);
        exit();
    }

    public function getManageBeta(){
      $this->_addHeaderFilesBeta();
      $this->assign['penjab'] = $this->db('penjab')->where('status', '1')->toArray();
      $this->assign['stts_nikah'] = array('BELUM MENIKAH','MENIKAH','JANDA','DUDHA','JOMBLO');
      $this->assign['agama'] = array('ISLAM', 'KRISTEN', 'PROTESTAN', 'HINDU', 'BUDHA', 'KONGHUCU', 'KEPERCAYAAN');
      $this->assign['pnd'] = array('TS','TK','SD','SMP','SMA','SLTA/SEDERAJAT','D1','D2','D3','D4','S1','S2','S3','-');
      $this->assign['keluarga'] = array('AYAH','IBU','ISTRI','SUAMI','SAUDARA','ANAK');
      $disabled_menu = $this->core->loadDisabledMenu('pasien'); 
      foreach ($disabled_menu as &$row) { 
        if ($row == "true" ) $row = "disabled"; 
      } 
      unset($row);

      return $this->draw('manage.beta.html', ['pasien' => $this->assign, 'disabled_menu' => $disabled_menu]);
    }

    public function getCssBeta()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/pasien/css/admin/styles.css');
        exit();
    }

    public function getJavascriptBeta()
    {
        header('Content-type: text/javascript');
        $settings = $this->settings('settings');
        echo $this->draw(MODULES.'/pasien/js/admin/scripts.js', ['settings' => $settings]);
        exit();
    }

    private function _addHeaderFilesBeta()
    {
        $this->core->addCSS(url('assets/css/datatables.min.css'));
        $this->core->addCSS(url('assets/css/jquery.contextMenu.min.css'));
        $this->core->addJS(url('assets/jscripts/jqueryvalidation.js'));
        $this->core->addJS(url('assets/jscripts/xlsx.js'));
        $this->core->addJS(url('assets/jscripts/jspdf.min.js'));
        $this->core->addJS(url('assets/jscripts/jspdf.plugin.autotable.min.js'));
        $this->core->addJS(url('assets/jscripts/datatables.min.js'));
        $this->core->addJS(url('assets/jscripts/jquery.contextMenu.min.js'));
        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));

        $this->core->addCSS(url([ADMIN, 'pasien', 'cssbeta']));
        $this->core->addJS(url([ADMIN, 'pasien', 'javascriptbeta']), 'footer');
    }  

}
