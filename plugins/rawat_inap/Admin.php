<?php
namespace Plugins\Rawat_Inap;

use Systems\AdminModule;
use Systems\Lib\BpjsService;

class Admin extends AdminModule
{

    private $_uploads = WEBAPPS_PATH.'/berkasrawat/pages/upload';
    protected array $assign = [];
    private $consid = '';
    private $secretkey = '';
    private $api_url = '';
    private $user_key = '';
    
    public function navigation()
    {
        return [
            'Kelola'   => 'manage',
        ];
    }

    public function anyManage()
    {
        $tgl_masuk = '';
        $tgl_masuk_akhir = '';
        $status_pulang = '';
        $status_periksa = '';
        if (!is_array($this->assign)) {
            $this->assign = []; // atau bisa langsung array dengan default
        }

        $this->assign['stts_pulang'] = [];


        if(isset($_POST['periode_rawat_inap'])) {
          $tgl_masuk = $_POST['periode_rawat_inap'];
        }
        if(isset($_POST['periode_rawat_inap_akhir'])) {
          $tgl_masuk_akhir = $_POST['periode_rawat_inap_akhir'];
        }
        if(isset($_POST['status_pulang'])) {
          $status_pulang = $_POST['status_pulang'];
        }
        if(isset($_POST['status_periksa'])) {
          $status_periksa = $_POST['status_periksa'];
        }
        $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
        $master_berkas_digital = $this->db('master_berkas_digital')->toArray();
        $this->_Display($tgl_masuk, $tgl_masuk_akhir, $status_pulang, $status_periksa);
        return $this->draw('manage.html', ['rawat_inap' => $this->assign, 'cek_vclaim' => $cek_vclaim, 'master_berkas_digital' => $master_berkas_digital]);
    }

    public function anyDisplay()
    {
        $tgl_masuk = '';
        $tgl_masuk_akhir = '';
        $status_pulang = '';
        $status_periksa = '';
        $this->assign['stts_pulang'] = [];

        if(isset($_POST['periode_rawat_inap'])) {
          $tgl_masuk = $_POST['periode_rawat_inap'];
        }
        if(isset($_POST['periode_rawat_inap_akhir'])) {
          $tgl_masuk_akhir = $_POST['periode_rawat_inap_akhir'];
        }
        if(isset($_POST['status_pulang'])) {
          $status_pulang = $_POST['status_pulang'];
        }
        if(isset($_POST['status_periksa'])) {
          $status_periksa = $_POST['status_periksa'];
        }
        $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
        $this->_Display($tgl_masuk, $tgl_masuk_akhir, $status_pulang, $status_periksa);
        echo $this->draw('display.html', ['rawat_inap' => $this->assign, 'cek_vclaim' => $cek_vclaim]);
        exit();
    }

    public function _Display($tgl_masuk='', $tgl_masuk_akhir='', $status_pulang='', $status_periksa='')
    {
        $this->_addHeaderFiles();

        $this->assign['kamar'] = $this->db('kamar')->join('bangsal', 'bangsal.kd_bangsal=kamar.kd_bangsal')->where('statusdata', '1')->toArray();
        $this->assign['dokter']         = $this->db('dokter')->where('status', '1')->toArray();
        $this->assign['penjab']       = $this->db('penjab')->where('status', '1')->toArray();
        $this->assign['no_rawat'] = '';

        $bangsal = str_replace(",","','", $this->core->getUserInfo('cap', null, true));

        $sql = "SELECT
            kamar_inap.*,
            reg_periksa.*,
            pasien.*,
            kamar.*,
            bangsal.*,
            penjab.*
          FROM
            kamar_inap,
            reg_periksa,
            pasien,
            kamar,
            bangsal,
            penjab
          WHERE
            kamar_inap.no_rawat=reg_periksa.no_rawat
          AND
            reg_periksa.no_rkm_medis=pasien.no_rkm_medis
          AND
            kamar_inap.kd_kamar=kamar.kd_kamar
          AND
            bangsal.kd_bangsal=kamar.kd_bangsal
          AND
            reg_periksa.kd_pj=penjab.kd_pj";

        if ($this->core->getUserInfo('role') != 'admin') {
          $sql .= " AND bangsal.kd_bangsal IN ('$bangsal')";
        }
        if($status_pulang == '') {
          $sql .= " AND kamar_inap.stts_pulang = '-'";
        }
        if($status_pulang == 'all' && $tgl_masuk !== '' && $tgl_masuk_akhir !== '') {
          $sql .= " AND kamar_inap.stts_pulang = '-' AND kamar_inap.tgl_masuk BETWEEN '$tgl_masuk' AND '$tgl_masuk_akhir'";
        }
        if($status_pulang == 'masuk' && $tgl_masuk !== '' && $tgl_masuk_akhir !== '') {
          $sql .= " AND kamar_inap.tgl_masuk BETWEEN '$tgl_masuk' AND '$tgl_masuk_akhir'";
        }
        if($status_pulang == 'pulang' && $tgl_masuk !== '' && $tgl_masuk_akhir !== '') {
          $sql .= " AND kamar_inap.tgl_keluar BETWEEN '$tgl_masuk' AND '$tgl_masuk_akhir'";
        }
        if($status_periksa == 'lunas' && $status_pulang == '-' && $tgl_masuk !== '' && $tgl_masuk_akhir !== '') {
          $sql .= " AND reg_periksa.status_bayar = 'Sudah Bayar' AND kamar_inap.tgl_masuk BETWEEN '$tgl_masuk' AND '$tgl_masuk_akhir'";
        }

        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $this->assign['list'] = [];
        foreach ($rows as $row) {
          $row['status_billing'] = 'Sudah Bayar';
          $get_billing = $this->db('mlite_billing')->where('no_rawat', $row['no_rawat'])->like('kd_billing', 'RI%')->oneArray();
          if(empty($get_billing['kd_billing'])) {
            $row['kd_billing'] = 'RI.'.date('d.m.Y.H.i.s');
            $row['tgl_billing'] = date('Y-m-d H:i');
            $row['status_billing'] = 'Belum Bayar';
          }

          $dpjp_ranap = $this->db('dpjp_ranap')
            ->join('dokter', 'dokter.kd_dokter=dpjp_ranap.kd_dokter')
            ->where('no_rawat', $row['no_rawat'])
            ->toArray();
          $row['dokter'] = $dpjp_ranap;
          $bridging_sep = $this->db('bridging_sep')->where('no_rawat', $row['no_rawat'])->oneArray();
          $row['no_sep'] = isset_or($bridging_sep['no_sep']);
          $this->assign['list'][] = $row;
        }

        if (isset($_POST['no_rawat'])){
          $this->assign['kamar_inap'] = $this->db('kamar_inap')
            ->join('reg_periksa', 'reg_periksa.no_rawat=kamar_inap.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
            ->join('kamar', 'kamar.kd_kamar=kamar_inap.kd_kamar')
            ->join('dpjp_ranap', 'dpjp_ranap.no_rawat=kamar_inap.no_rawat')
            ->join('dokter', 'dokter.kd_dokter=dpjp_ranap.kd_dokter')
            ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
            ->where('kamar_inap.no_rawat', $_POST['no_rawat'])
            ->oneArray();
        } else {
          $this->assign['kamar_inap'] = [
            'tgl_masuk' => date('Y-m-d'),
            'jam_masuk' => date('H:i:s'),
            'tgl_keluar' => date('Y-m-d'),
            'jam_keluar' => date('H:i:s'),
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'no_rawat' => '',
            'kd_dokter' => '',
            'kd_kamar' => '',
            'kd_pj' => '',
            'diagnosa_awal' => '',
            'diagnosa_akhir' => '',
            'stts_pulang' => '',
            'lama' => ''
          ];
        }
    }

    public function anyForm()
    {

      $this->assign['kamar'] = $this->db('kamar')->join('bangsal', 'bangsal.kd_bangsal=kamar.kd_bangsal')->where('statusdata', '1')->toArray();
      $this->assign['dokter'] = $this->db('dokter')->where('status', '1')->toArray();
      $this->assign['penjab'] = $this->db('penjab')->where('status', '1')->toArray();
      $this->assign['stts_pulang'] = ['Sehat','Rujuk','APS','+','Meninggal','Sembuh','Membaik','Pulang Paksa','-','Pindah Kamar','Status Belum Lengkap','Atas Persetujuan Dokter','Atas Permintaan Sendiri','Lain-lain'];
      $this->assign['no_rawat'] = '';
      if (isset($_POST['no_rawat'])){
        $this->assign['kamar_inap'] = $this->db('kamar_inap')
          ->join('reg_periksa', 'reg_periksa.no_rawat=kamar_inap.no_rawat')
          ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
          ->join('kamar', 'kamar.kd_kamar=kamar_inap.kd_kamar')
          ->join('dpjp_ranap', 'dpjp_ranap.no_rawat=kamar_inap.no_rawat')
          ->join('dokter', 'dokter.kd_dokter=dpjp_ranap.kd_dokter')
          ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
          ->where('kamar_inap.no_rawat', $_POST['no_rawat'])
          ->oneArray();
        echo $this->draw('form.html', [
          'rawat_inap' => $this->assign
        ]);
      } else {
        $this->assign['kamar_inap'] = [
          'tgl_masuk' => date('Y-m-d'),
          'jam_masuk' => date('H:i:s'),
          'tgl_keluar' => date('Y-m-d'),
          'jam_keluar' => date('H:i:s'),
          'no_rkm_medis' => '',
          'nm_pasien' => '',
          'no_rawat' => '',
          'kd_dokter' => '',
          'kd_kamar' => '',
          'kd_pj' => '',
          'diagnosa_awal' => '',
          'diagnosa_akhir' => '',
          'stts_pulang' => '',
          'lama' => ''
        ];
        echo $this->draw('form.html', [
          'rawat_inap' => $this->assign
        ]);
      }
      exit();
    }

    public function anyStatusDaftar()
    {
      if(isset($_POST['no_rkm_medis'])) {
        $rawat = $this->db('reg_periksa')
          ->where('no_rkm_medis', $_POST['no_rkm_medis'])
          ->where('status_bayar', 'Belum Bayar')
          ->limit(1)
          ->oneArray();
          if($rawat) {
            $stts_daftar ="Transaki tanggal ".date('Y-m-d', strtotime($rawat['tgl_registrasi']))." belum diselesaikan" ;
            $bg_status = 'text-danger';
          } else {
            $result = $this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();
            if(!empty($result['no_rawat'])) {
              $stts_daftar = 'Lama';
              $bg_status = 'text-info';
            } else {
              $stts_daftar = 'Baru';
              $bg_status = 'text-success';
            }
          }
        echo $this->draw('stts.daftar.html', ['stts_daftar' => $stts_daftar, 'stts_daftar_hidden' => $stts_daftar, 'bg_status' => $bg_status]);
      } else {
        $rawat = $this->db('reg_periksa')
          ->where('no_rawat', $_POST['no_rawat'])
          ->oneArray();
        echo $this->draw('stts.daftar.html', ['stts_daftar' => $rawat['stts_daftar'], 'stts_daftar_hidden' => $rawat['stts_daftar'], 'bg_status' => 'text-info']);
      }
      exit();
    }

    public function postSave()
    {
      $kamar = $this->db('kamar')->where('kd_kamar', $_POST['kd_kamar'])->oneArray();
      $kamar_inap = $this->db('kamar_inap')->save([
        'no_rawat' => $_POST['no_rawat'],
        'kd_kamar' => $_POST['kd_kamar'],
        'trf_kamar' => $kamar['trf_kamar'],
        'lama' => $_POST['lama'],
        'tgl_masuk' => $_POST['tgl_masuk'],
        'jam_masuk' => $_POST['jam_masuk'],
        'ttl_biaya' => $kamar['trf_kamar']*$_POST['lama'],
        'tgl_keluar' => null,
        'jam_keluar' => null,
        'diagnosa_akhir' => '',
        'diagnosa_awal' => $_POST['diagnosa_awal'],
        'stts_pulang' => '-'
      ]);
      if($kamar_inap) {
        $this->db('dpjp_ranap')->save(['no_rawat' => $_POST['no_rawat'], 'kd_dokter' => $_POST['kd_dokter']]);
        $this->db('kamar')->where('kd_kamar', $_POST['kd_kamar'])->save(['status' => 'ISI']);
      }
      exit();
    }

    public function postSaveKeluar()
    {
      $kamar = $this->db('kamar')->where('kd_kamar', $_POST['kd_kamar'])->oneArray();
      $this->db('kamar_inap')->where('no_rawat', $_POST['no_rawat'])->save([
        'stts_pulang' => $_POST['stts_pulang'],
        'lama' => $_POST['lama'],
        'tgl_keluar' => $_POST['tgl_keluar'],
        'jam_keluar' => $_POST['jam_keluar'],
        'diagnosa_akhir' => $_POST['diagnosa_akhir'],
        'ttl_biaya' => $kamar['trf_kamar']*$_POST['lama']
      ]);
      $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->save([
        'kd_pj' => $_POST['kd_pj'],
        'stts' => 'Sudah'
      ]);
      $this->db('kamar')->where('kd_kamar', $_POST['kd_kamar'])->save(['status' => 'KOSONG']);
      exit();
    }

    public function postSetDPJP()
    {
      $this->db('dpjp_ranap')->save(['no_rawat' => $_POST['no_rawat'], 'kd_dokter' => $_POST['kd_dokter']]);
      exit();
    }

    public function postHapusDPJP()
    {
      $this->db('dpjp_ranap')->where('no_rawat', $_POST['no_rawat'])->where('kd_dokter', $_POST['kd_dokter'])->delete();
      exit();
    }

    public function postUbahPenjab()
    {
      $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->save([
        'kd_pj' => $_POST['kd_pj']
      ]);
      exit();
    }

    public function anyPasien()
    {
      $cari = $_POST['cari'];
      if(isset($_POST['cari'])) {
        $sql = "SELECT
            pasien.nm_pasien,
            pasien.no_rkm_medis,
            reg_periksa.no_rawat
          FROM
            reg_periksa,
            pasien
          WHERE
            reg_periksa.status_lanjut='Ranap'
          AND
            pasien.no_rkm_medis=reg_periksa.no_rkm_medis
          AND
            (reg_periksa.no_rkm_medis LIKE ? OR reg_periksa.no_rawat LIKE ? OR pasien.nm_pasien LIKE ?)
          LIMIT 10";

        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute(['%'.$cari.'%', '%'.$cari.'%', '%'.$cari.'%']);
        $pasien = $stmt->fetchAll();

        /*$pasien = $this->db('reg_periksa')
          ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
          ->like('reg_periksa.no_rkm_medis', '%'.$_POST['cari'].'%')
          ->where('status_lanjut', 'Ranap')
          ->asc('reg_periksa.no_rkm_medis')
          ->limit(15)
          ->toArray();*/

      }
      echo $this->draw('pasien.html', ['pasien' => $pasien]);
      exit();
    }

    public function getAntrian()
    {
      $settings = $this->settings('settings');
      $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
      $rawat_inap = $this->db('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
        ->where('no_rawat', $_GET['no_rawat'])
        ->oneArray();
      echo $this->draw('antrian.html', ['rawat_inap' => $rawat_inap]);
      exit();
    }

    public function postHapus()
    {
      $this->db('kamar_inap')->where('no_rawat', $_POST['no_rawat'])->delete();
      exit();
    }

    public function postSaveDetail()
    {
      if($_POST['kat'] == 'tindakan') {
        $jns_perawatan = $this->db('jns_perawatan_inap')->where('kd_jenis_prw', $_POST['kd_jenis_prw'])->oneArray();
        if($_POST['provider'] == 'rawat_inap_dr') {
          $this->db('rawat_inap_dr')->save([
            'no_rawat' => $_POST['no_rawat'],
            'kd_jenis_prw' => $_POST['kd_jenis_prw'],
            'kd_dokter' => $_POST['kode_provider'],
            'tgl_perawatan' => $_POST['tgl_perawatan'],
            'jam_rawat' => $_POST['jam_rawat'],
            'material' => $jns_perawatan['material'],
            'bhp' => $jns_perawatan['bhp'],
            'tarif_tindakandr' => $jns_perawatan['tarif_tindakandr'],
            'kso' => $jns_perawatan['kso'],
            'menejemen' => $jns_perawatan['menejemen'],
            'biaya_rawat' => $jns_perawatan['total_byrdr']
          ]);
        }
        if($_POST['provider'] == 'rawat_inap_pr') {
          $this->db('rawat_inap_pr')->save([
            'no_rawat' => $_POST['no_rawat'],
            'kd_jenis_prw' => $_POST['kd_jenis_prw'],
            'nip' => $_POST['kode_provider2'],
            'tgl_perawatan' => $_POST['tgl_perawatan'],
            'jam_rawat' => $_POST['jam_rawat'],
            'material' => $jns_perawatan['material'],
            'bhp' => $jns_perawatan['bhp'],
            'tarif_tindakanpr' => $jns_perawatan['tarif_tindakanpr'],
            'kso' => $jns_perawatan['kso'],
            'menejemen' => $jns_perawatan['menejemen'],
            'biaya_rawat' => $jns_perawatan['total_byrpr']
          ]);
        }
        if($_POST['provider'] == 'rawat_inap_drpr') {
          $this->db('rawat_inap_drpr')->save([
            'no_rawat' => $_POST['no_rawat'],
            'kd_jenis_prw' => $_POST['kd_jenis_prw'],
            'kd_dokter' => $_POST['kode_provider'],
            'nip' => $_POST['kode_provider2'],
            'tgl_perawatan' => $_POST['tgl_perawatan'],
            'jam_rawat' => $_POST['jam_rawat'],
            'material' => $jns_perawatan['material'],
            'bhp' => $jns_perawatan['bhp'],
            'tarif_tindakandr' => $jns_perawatan['tarif_tindakandr'],
            'tarif_tindakanpr' => $jns_perawatan['tarif_tindakanpr'],
            'kso' => $jns_perawatan['kso'],
            'menejemen' => $jns_perawatan['menejemen'],
            'biaya_rawat' => $jns_perawatan['total_byrdrpr']
          ]);
        }
      }
      if($_POST['kat'] == 'obat') {

        $no_resep = $this->core->setNoResep($_POST['tgl_perawatan']);
        $cek_resep = $this->db('resep_obat')->where('no_rawat', $_POST['no_rawat'])->where('tgl_peresepan', $_POST['tgl_perawatan'])->where('tgl_perawatan', 'IS', 'NULL')->where('status', 'ranap')->oneArray();

        if(empty($cek_resep)) {

          $resep_obat = $this->db('resep_obat')
            ->save([
              'no_resep' => $no_resep,
              'tgl_perawatan' => null,
              'jam' => null,
              'no_rawat' => $_POST['no_rawat'],
              'kd_dokter' => $_POST['kode_provider'],
              'tgl_peresepan' => $_POST['tgl_perawatan'],
              'jam_peresepan' => $_POST['jam_rawat'],
              'status' => 'ranap',
              'tgl_penyerahan' => null,
              'jam_penyerahan' => null
            ]);

          if ($this->db('resep_obat')->where('no_resep', $no_resep)->where('kd_dokter', $_POST['kode_provider'])->oneArray()) {
            $this->db('resep_dokter')
              ->save([
                'no_resep' => $no_resep,
                'kode_brng' => $_POST['kd_jenis_prw'],
                'jml' => $_POST['jml'],
                'aturan_pakai' => $_POST['aturan_pakai']
              ]);
          }

        } else {

          $no_resep = $cek_resep['no_resep'];

          $this->db('resep_dokter')
            ->save([
              'no_resep' => $no_resep,
              'kode_brng' => $_POST['kd_jenis_prw'],
              'jml' => $_POST['jml'],
              'aturan_pakai' => $_POST['aturan_pakai']
            ]);

        }

      }
      exit();
    }

    public function postHapusDetail()
    {
      if($_POST['provider'] == 'rawat_inap_dr') {
        $this->db('rawat_inap_dr')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_perawatan', $_POST['tgl_perawatan'])
        ->where('jam_rawat', $_POST['jam_rawat'])
        ->delete();
      }
      if($_POST['provider'] == 'rawat_inap_pr') {
        $this->db('rawat_inap_pr')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_perawatan', $_POST['tgl_perawatan'])
        ->where('jam_rawat', $_POST['jam_rawat'])
        ->delete();
      }
      if($_POST['provider'] == 'rawat_inap_drpr') {
        $this->db('rawat_inap_drpr')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_perawatan', $_POST['tgl_perawatan'])
        ->where('jam_rawat', $_POST['jam_rawat'])
        ->delete();
      }
      exit();
    }

    public function postHapusResep()
    {
      if(isset($_POST['kd_jenis_prw'])) {
        $this->db('resep_dokter')
        ->where('no_resep', $_POST['no_resep'])
        ->where('kode_brng', $_POST['kd_jenis_prw'])
        ->delete();
      } else {
        $this->db('resep_obat')
        ->where('no_resep', $_POST['no_resep'])
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('tgl_peresepan', $_POST['tgl_peresepan'])
        ->where('jam_peresepan', $_POST['jam_peresepan'])
        ->delete();
      }

      exit();
    }

    public function anyRincian()
    {
      $rows_rawat_inap_dr = $this->db('rawat_inap_dr')->where('no_rawat', $_POST['no_rawat'])->toArray();
      $rows_rawat_inap_pr = $this->db('rawat_inap_pr')->where('no_rawat', $_POST['no_rawat'])->toArray();
      $rows_rawat_inap_drpr = $this->db('rawat_inap_drpr')->where('no_rawat', $_POST['no_rawat'])->toArray();

      $jumlah_total = 0;
      $rawat_inap_dr = [];
      $rawat_inap_pr = [];
      $rawat_inap_drpr = [];
      $i = 1;

      if($rows_rawat_inap_dr) {
        foreach ($rows_rawat_inap_dr as $row) {
          $jns_perawatan = $this->db('jns_perawatan_inap')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
          $row['nm_perawatan'] = $jns_perawatan['nm_perawatan'];
          $jumlah_total = $jumlah_total + $row['biaya_rawat'];
          $row['provider'] = 'rawat_inap_dr';
          $rawat_inap_dr[] = $row;
        }
      }

      if($rows_rawat_inap_pr) {
        foreach ($rows_rawat_inap_pr as $row) {
          $jns_perawatan = $this->db('jns_perawatan_inap')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
          $row['nm_perawatan'] = $jns_perawatan['nm_perawatan'];
          $jumlah_total = $jumlah_total + $row['biaya_rawat'];
          $row['provider'] = 'rawat_inap_pr';
          $rawat_inap_pr[] = $row;
        }
      }

      if($rows_rawat_inap_drpr) {
        foreach ($rows_rawat_inap_drpr as $row) {
          $jns_perawatan = $this->db('jns_perawatan_inap')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
          $row['nm_perawatan'] = $jns_perawatan['nm_perawatan'];
          $jumlah_total = $jumlah_total + $row['biaya_rawat'];
          $row['provider'] = 'rawat_inap_drpr';
          $rawat_inap_drpr[] = $row;
        }
      }

      $rows = $this->db('resep_obat')
        ->join('dokter', 'dokter.kd_dokter=resep_obat.kd_dokter')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('resep_obat.status', 'ranap')
        ->toArray();
      $resep = [];
      $jumlah_total_resep = 0;
      foreach ($rows as $row) {
        $row['nomor'] = $i++;
        $row['resep_dokter'] = $this->db('resep_dokter')->join('databarang', 'databarang.kode_brng=resep_dokter.kode_brng')->where('no_resep', $row['no_resep'])->toArray();
        foreach ($row['resep_dokter'] as $value) {
          $value['dasar'] = $value['jml'] * $value['dasar'];
          $jumlah_total_resep += floatval($value['dasar']);
        }
        $resep[] = $row;
      }
      echo $this->draw('rincian.html', ['rawat_inap_dr' => $rawat_inap_dr, 'rawat_inap_pr' => $rawat_inap_pr, 'rawat_inap_drpr' => $rawat_inap_drpr, 'jumlah_total' => $jumlah_total, 'jumlah_total_resep' => $jumlah_total_resep, 'resep' =>$resep, 'no_rawat' => $_POST['no_rawat']]);
      exit();
    }

    public function anySoap()
    {

      $prosedurs = $this->db('prosedur_pasien')
         ->where('no_rawat', $_POST['no_rawat'])
         ->asc('prioritas')
         ->toArray();
       $prosedur = [];
       foreach ($prosedurs as $row) {
         $icd9 = $this->db('icd9')->where('kode', $row['kode'])->oneArray();
         $row['nama'] = $icd9['deskripsi_panjang'];
         $prosedur[] = $row;
       }
       $diagnosas = $this->db('diagnosa_pasien')
         ->where('no_rawat', $_POST['no_rawat'])
         ->asc('prioritas')
         ->toArray();
       $diagnosa = [];
       foreach ($diagnosas as $row) {
         $icd10 = $this->db('penyakit')->where('kd_penyakit', $row['kd_penyakit'])->oneArray();
         $row['nama'] = $icd10['nm_penyakit'];
         $diagnosa[] = $row;
       }

      $i = 1;
      $row['nama_petugas'] = '';
      $row['departemen_petugas'] = '';
      $rows = $this->db('pemeriksaan_ralan')
        ->where('no_rawat', $_POST['no_rawat'])
        ->toArray();
      $result = [];
      foreach ($rows as $row) {
        $row['nomor'] = $i++;
        $row['nama_petugas'] = $this->core->getPegawaiInfo('nama',$row['nip']);
        $row['departemen_petugas'] = $this->core->getDepartemenInfo($this->core->getPegawaiInfo('departemen',$row['nip']));
        $result[] = $row;
      }

      $rows_ranap = $this->db('pemeriksaan_ranap')
        ->where('no_rawat', $_POST['no_rawat'])
        ->toArray();
      $result_ranap = [];
      foreach ($rows_ranap as $row) {
        $row['nomor'] = $i++;
        $row['nama_petugas'] = $this->core->getPegawaiInfo('nama',$row['nip']);
        $row['departemen_petugas'] = $this->core->getDepartemenInfo($this->core->getPegawaiInfo('departemen',$row['nip']));
        $result_ranap[] = $row;
      }

      echo $this->draw('soap.html', ['pemeriksaan' => $result, 'pemeriksaan_ranap' => $result_ranap, 'diagnosa' => $diagnosa, 'prosedur' => $prosedur]);
      exit();
    }

    public function anyFormSoap()
    {
      // Ambil data pemeriksaan_ralan terbaru sebagai fallback
      $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')
        ->where('no_rawat', $_POST['no_rawat'])
        ->desc('tgl_perawatan')
        ->desc('jam_rawat')
        ->oneArray();
      
      // Set default values dari pemeriksaan_ralan jika ada
      $default_values = [
        'suhu_tubuh' => $pemeriksaan_ralan['suhu_tubuh'] ?? '',
        'tensi' => $pemeriksaan_ralan['tensi'] ?? '',
        'nadi' => $pemeriksaan_ralan['nadi'] ?? '',
        'respirasi' => $pemeriksaan_ralan['respirasi'] ?? '',
        'tinggi' => $pemeriksaan_ralan['tinggi'] ?? '',
        'berat' => $pemeriksaan_ralan['berat'] ?? '',
        'gcs' => $pemeriksaan_ralan['gcs'] ?? '',
        'kesadaran' => $pemeriksaan_ralan['kesadaran'] ?? '',
        'alergi' => $pemeriksaan_ralan['alergi'] ?? '',
        'lingkar_perut' => $pemeriksaan_ralan['lingkar_perut'] ?? '',
        'keluhan' => $pemeriksaan_ralan['keluhan'] ?? '',
        'pemeriksaan' => $pemeriksaan_ralan['pemeriksaan'] ?? '',
        'penilaian' => $pemeriksaan_ralan['penilaian'] ?? '',
        'rtl' => $pemeriksaan_ralan['rtl'] ?? '',
        'instruksi' => $pemeriksaan_ralan['instruksi'] ?? '',
        'evaluasi' => $pemeriksaan_ralan['evaluasi'] ?? '',
        'spo2' => $pemeriksaan_ralan['spo2'] ?? ''
      ];
      
      echo $this->draw('form.soap.html', ['default_values' => $default_values]);
      exit();
    }

    public function postSaveSOAP()
    {
      $_POST['nip'] = $this->core->getUserInfo('username', null, true);

      if(!$this->db('pemeriksaan_ranap')->where('no_rawat', $_POST['no_rawat'])->where('tgl_perawatan', $_POST['tgl_perawatan'])->where('jam_rawat', $_POST['jam_rawat'])->where('nip', $_POST['nip'])->oneArray()) {
        $this->db('pemeriksaan_ranap')->save($_POST);
      } else {
        $this->db('pemeriksaan_ranap')->where('no_rawat', $_POST['no_rawat'])->where('tgl_perawatan', $_POST['tgl_perawatan'])->where('jam_rawat', $_POST['jam_rawat'])->where('nip', $_POST['nip'])->save($_POST);
      }
      exit();
    }

    public function postHapusSOAP()
    {
      $this->db('pemeriksaan_ranap')->where('no_rawat', $_POST['no_rawat'])->where('tgl_perawatan', $_POST['tgl_perawatan'])->where('jam_rawat', $_POST['jam_rawat'])->delete();
      exit();
    }

    public function anyLayanan()
    {
      $layanan = $this->db('jns_perawatan_inap')
        ->where('status', '1')
        ->like('nm_perawatan', '%'.$_POST['layanan'].'%')
        ->limit(10)
        ->toArray();
      echo $this->draw('layanan.html', ['layanan' => $layanan]);
      exit();
    }

    public function anyObat()
    {
      $obat = $this->db('databarang')
        ->join('gudangbarang', 'gudangbarang.kode_brng=databarang.kode_brng')
        ->where('status', '1')
        ->where('gudangbarang.kd_bangsal', $this->settings->get('farmasi.deporanap'))
        ->like('databarang.nama_brng', '%'.$_POST['obat'].'%')
        ->limit(10)
        ->toArray();
      echo $this->draw('obat.html', ['obat' => $obat]);
      exit();
    }

    public function postAturanPakai()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('master_aturan_pakai')->like('aturan', $key)->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.$row["aturan"].'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function anyBerkasDigital()
    {
      $berkas_digital = $this->db('berkas_digital_perawatan')->where('no_rawat', $_POST['no_rawat'])->toArray();
      echo $this->draw('berkasdigital.html', ['berkas_digital' => $berkas_digital]);
      exit();
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

        $image = $_FILES['file']['tmp_name'];
        $img = new \Systems\Lib\Image();
        $id = convertNorawat($_POST['no_rawat']);
        if ($img->load($image)) {
            $imgName = time().$cntr++;
            $imgPath = $dir.'/'.$id.'_'.$imgName.'.'.$img->getInfos('type');
            $lokasi_file = 'pages/upload/'.$id.'_'.$imgName.'.'.$img->getInfos('type');
            $img->save($imgPath);
            $query = $this->db('berkas_digital_perawatan')->save(['no_rawat' => $_POST['no_rawat'], 'kode' => $_POST['kode'], 'lokasi_file' => $lokasi_file]);
            if($query) {
              echo '<br><img src="'.WEBAPPS_URL.'/berkasrawat/'.$lokasi_file.'" width="150" />';
            }
        }
      }
      exit();

    }

    public function postProviderList()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('dokter')->like('nm_dokter', $key)->where('status', '1')->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.$row["kd_dokter"].': '.$row["nm_dokter"].'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function postProviderList2()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('petugas')->like('nama', $key)->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.$row["nip"].': '.$row["nama"].'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function postCekWaktu()
    {
      echo date('H:i:s');
      exit();
    }

    public function postMaxid()
    {
      $max_id = $this->db('reg_periksa')->select(['no_rawat' => 'ifnull(MAX(CONVERT(RIGHT(no_rawat,6),signed)),0)'])->where('tgl_registrasi', date('Y-m-d'))->oneArray();
      if(empty($max_id['no_rawat'])) {
        $max_id['no_rawat'] = '000000';
      }
      $_next_no_rawat = sprintf('%06s', ($max_id['no_rawat'] + 1));
      $next_no_rawat = date('Y/m/d').'/'.$_next_no_rawat;
      echo $next_no_rawat;
      exit();
    }

    public function postMaxAntrian()
    {
      $max_id = $this->db('reg_periksa')->select(['no_reg' => 'ifnull(MAX(CONVERT(RIGHT(no_reg,3),signed)),0)'])->where('kd_poli', $_POST['kd_poli'])->where('tgl_registrasi', date('Y-m-d'))->desc('no_reg')->limit(1)->oneArray();
      if(empty($max_id['no_reg'])) {
        $max_id['no_reg'] = '000';
      }
      $_next_no_reg = sprintf('%03s', ($max_id['no_reg'] + 1));
      echo $_next_no_reg;
      exit();
    }

    public function convertNorawat($text)
    {
        setlocale(LC_ALL, 'en_EN');
        $text = str_replace('/', '', trim($text));
        return $text;
    }

    public function getSepDetail($no_sep){
      $sep = $this->db('bridging_sep')->where('no_sep', $no_sep)->oneArray();
      $this->tpl->set('sep', $this->tpl->noParse_array(htmlspecialchars_array($sep)));

      $potensi_prb = $this->db('bpjs_prb')->where('no_sep', $no_sep)->oneArray();
      $data_sep['potensi_prb'] = $potensi_prb['prb'];
      echo $this->draw('sep.detail.html', ['data_sep' => $data_sep]);
      exit();
    }

    public function getSuratRujukan($no_rawat)
    {
        $kd_dokter = $this->core->getRegPeriksaInfo('kd_dokter', revertNoRawat($no_rawat));
        $no_rkm_medis = $this->core->getRegPeriksaInfo('no_rkm_medis', revertNoRawat($no_rawat));
        $pasien = $this->db('pasien')
          ->join('kelurahan', 'kelurahan.kd_kel=pasien.kd_kel')
          ->join('kecamatan', 'kecamatan.kd_kec=pasien.kd_kec')
          ->join('kabupaten', 'kabupaten.kd_kab=pasien.kd_kab')
          ->join('propinsi', 'propinsi.kd_prop=pasien.kd_prop')
          ->where('no_rkm_medis', $no_rkm_medis)
          ->oneArray();
        $nm_dokter = $this->core->getPegawaiInfo('nama', $kd_dokter);
        $sip_dokter = $this->core->getDokterInfo('no_ijn_praktek', $kd_dokter);
        $this->tpl->set('pasien', $this->tpl->noParse_array(htmlspecialchars_array($pasien)));
        $this->tpl->set('nm_dokter', $nm_dokter);
        $this->tpl->set('sip_dokter', $sip_dokter);
        $this->tpl->set('no_rawat', revertNoRawat($no_rawat));
        $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($this->settings('settings'))));
        $this->tpl->set('surat', $this->db('mlite_surat_rujukan')->where('no_rawat', revertNoRawat($no_rawat))->oneArray());
        $this->tpl->set('nomor_surat', $this->settings->get('settings.set_nomor_surat').'/'.$this->settings->get('settings.prefix_surat').'/'.getRomawi(date('m')).'/'.date('Y'));
        echo $this->tpl->draw(MODULES.'/rawat_inap/view/admin/surat.rujukan.html', true);
        exit();
    }

    public function getSuratSehat($no_rawat)
    {
        $kd_dokter = $this->core->getRegPeriksaInfo('kd_dokter', revertNoRawat($no_rawat));
        $no_rkm_medis = $this->core->getRegPeriksaInfo('no_rkm_medis', revertNoRawat($no_rawat));
        $pasien = $this->db('pasien')
          ->join('kelurahan', 'kelurahan.kd_kel=pasien.kd_kel')
          ->join('kecamatan', 'kecamatan.kd_kec=pasien.kd_kec')
          ->join('kabupaten', 'kabupaten.kd_kab=pasien.kd_kab')
          ->join('propinsi', 'propinsi.kd_prop=pasien.kd_prop')
          ->where('no_rkm_medis', $no_rkm_medis)
          ->oneArray();
        $nm_dokter = $this->core->getPegawaiInfo('nama', $kd_dokter);
        $sip_dokter = $this->core->getDokterInfo('no_ijn_praktek', $kd_dokter);
        $this->tpl->set('pasien', $this->tpl->noParse_array(htmlspecialchars_array($pasien)));
        $this->tpl->set('nm_dokter', $nm_dokter);
        $this->tpl->set('sip_dokter', $sip_dokter);
        $this->tpl->set('no_rawat', revertNoRawat($no_rawat));
        $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($this->settings('settings'))));
        $this->tpl->set('surat', $this->db('mlite_surat_sehat')->where('no_rawat', revertNoRawat($no_rawat))->oneArray());
        $this->tpl->set('nomor_surat', $this->settings->get('settings.set_nomor_surat').'/'.$this->settings->get('settings.prefix_surat').'/'.getRomawi(date('m')).'/'.date('Y'));
        echo $this->tpl->draw(MODULES.'/rawat_inap/view/admin/surat.sehat.html', true);
        exit();
    }

    public function getSuratSakit($no_rawat)
    {
        $kd_dokter = $this->core->getRegPeriksaInfo('kd_dokter', revertNoRawat($no_rawat));
        $no_rkm_medis = $this->core->getRegPeriksaInfo('no_rkm_medis', revertNoRawat($no_rawat));
        $pasien = $this->db('pasien')
          ->join('kelurahan', 'kelurahan.kd_kel=pasien.kd_kel')
          ->join('kecamatan', 'kecamatan.kd_kec=pasien.kd_kec')
          ->join('kabupaten', 'kabupaten.kd_kab=pasien.kd_kab')
          ->join('propinsi', 'propinsi.kd_prop=pasien.kd_prop')
          ->where('no_rkm_medis', $no_rkm_medis)
          ->oneArray();
        $nm_dokter = $this->core->getPegawaiInfo('nama', $kd_dokter);
        $sip_dokter = $this->core->getDokterInfo('no_ijn_praktek', $kd_dokter);
        $this->tpl->set('pasien', $this->tpl->noParse_array(htmlspecialchars_array($pasien)));
        $this->tpl->set('nm_dokter', $nm_dokter);
        $this->tpl->set('sip_dokter', $sip_dokter);
        $this->tpl->set('no_rawat', revertNoRawat($no_rawat));
        $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($this->settings('settings'))));
        $this->tpl->set('surat', $this->db('mlite_surat_sakit')->where('no_rawat', revertNoRawat($no_rawat))->oneArray());
        $this->tpl->set('nomor_surat', $this->settings->get('settings.set_nomor_surat').'/'.$this->settings->get('settings.prefix_surat').'/'.getRomawi(date('m')).'/'.date('Y'));
        echo $this->tpl->draw(MODULES.'/rawat_inap/view/admin/surat.sakit.html', true);
        exit();
    }

    public function postSimpanSuratSakit()
    {
      $query = $this->db('mlite_surat_sakit')->save([
        'id' => NULL, 
        'nomor_surat' => $_POST['nomor_surat'], 
        'no_rawat' => $_POST['no_rawat'], 
        'no_rkm_medis' => $_POST['no_rkm_medis'], 
        'nm_pasien' => $_POST['nm_pasien'], 
        'tgl_lahir' => $_POST['tgl_lahir'], 
        'umur' => $_POST['umur'], 
        'jk' => $_POST['jk'], 
        'alamat' => $_POST['alamat'], 
        'keadaan' => $_POST['keadaan'], 
        'diagnosa' => $_POST['diagnosa'], 
        'lama_angka' => $_POST['lama_angka'], 
        'lama_huruf' => $_POST['lama_huruf'], 
        'tanggal_mulai' => $_POST['tanggal_mulai'], 
        'tanggal_selesai' => $_POST['tanggal_selesai'], 
        'dokter' => $_POST['dokter'], 
        'petugas' => $_POST['petugas']
      ]);

      if($query) {
        $nomor_surat = ltrim($this->settings->get('settings.set_nomor_surat'));
        $nomor_surat = sprintf('%03s', ($nomor_surat + 1));
        $this->db('mlite_settings')->where('module', 'settings')->where('field', 'set_nomor_surat')->set('value', $nomor_surat)->update();
        $data['status'] = 'success';
        echo json_encode($data);
      } else {
        $data['status'] = 'error';
        $data['msg'] = $query->errorInfo()['2'];
        echo json_encode($data);
      }

      exit();
    }

    public function postSimpanSuratSehat()
    {
      $query = $this->db('mlite_surat_sehat')->save([
        'id' => NULL, 
        'nomor_surat' => $_POST['nomor_surat'], 
        'no_rawat' => $_POST['no_rawat'], 
        'no_rkm_medis' => $_POST['no_rkm_medis'], 
        'nm_pasien' => $_POST['nm_pasien'], 
        'tgl_lahir' => $_POST['tgl_lahir'], 
        'umur' => $_POST['umur'], 
        'jk' => $_POST['jk'], 
        'alamat' => $_POST['alamat'], 
        'tanggal' => $_POST['tanggal'], 
        'berat_badan' => $_POST['berat_badan'], 
        'tinggi_badan' => $_POST['tinggi_badan'], 
        'tensi' => $_POST['tensi'], 
        'gol_darah' => $_POST['gol_darah'], 
        'riwayat_penyakit' => $_POST['riwayat_penyakit'], 
        'keperluan' => $_POST['keperluan'], 
        'dokter' => $_POST['dokter'], 
        'petugas' => $_POST['petugas']
      ]);

      if($query) {
        $nomor_surat = ltrim($this->settings->get('settings.set_nomor_surat'));
        $nomor_surat = sprintf('%03s', ($nomor_surat + 1));
        $this->db('mlite_settings')->where('module', 'settings')->where('field', 'set_nomor_surat')->set('value', $nomor_surat)->update();
        $data['status'] = 'success';
        echo json_encode($data);
      } else {
        $data['status'] = 'error';
        $data['msg'] = $query->errorInfo()['2'];
        echo json_encode($data);
      }

      exit();
    }

    public function postSimpanSuratRujukan()
    {
      $query = $this->db('mlite_surat_rujukan')->save([
        'id' => NULL, 
        'nomor_surat' => $_POST['nomor_surat'], 
        'no_rawat' => $_POST['no_rawat'], 
        'no_rkm_medis' => $_POST['no_rkm_medis'], 
        'nm_pasien' => $_POST['nm_pasien'], 
        'tgl_lahir' => $_POST['tgl_lahir'], 
        'umur' => $_POST['umur'], 
        'jk' => $_POST['jk'], 
        'alamat' => $_POST['alamat'], 
        'kepada' => $_POST['kepada'], 
        'di' => $_POST['di'], 
        'anamnesa' => $_POST['anamnesa'], 
        'pemeriksaan_fisik' => $_POST['pemeriksaan_fisik'], 
        'pemeriksaan_penunjang' => $_POST['pemeriksaan_penunjang'], 
        'diagnosa' => $_POST['diagnosa'], 
        'terapi' => $_POST['terapi'], 
        'alasan_dirujuk' => $_POST['alasan_dirujuk'], 
        'dokter' => $_POST['dokter'], 
        'petugas' => $_POST['petugas']
      ]);

      if($query) {
        $nomor_surat = ltrim($this->settings->get('settings.set_nomor_surat'));
        $nomor_surat = sprintf('%03s', ($nomor_surat + 1));
        $this->db('mlite_settings')->where('module', 'settings')->where('field', 'set_nomor_surat')->set('value', $nomor_surat)->update();
        $data['status'] = 'success';
        echo json_encode($data);
      } else {
        $data['status'] = 'error';
        $data['msg'] = $query->errorInfo()['2'];
        echo json_encode($data);
      }

      exit();
    }

    public function anyKontrol()
    {
      $rows = $this->db('booking_registrasi')
        ->join('poliklinik', 'poliklinik.kd_poli=booking_registrasi.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=booking_registrasi.kd_dokter')
        ->join('penjab', 'penjab.kd_pj=booking_registrasi.kd_pj')
        ->where('no_rkm_medis', $_POST['no_rkm_medis'])
        ->toArray();
      $i = 1;
      $result = [];
      foreach ($rows as $row) {
        $row['nomor'] = $i++;
        $result[] = $row;
      }
      echo $this->draw('kontrol.html', ['booking_registrasi' => $result]);
      exit();
    }

    public function postSaveKontrol()
    {

      $query = $this->db('skdp_bpjs')->save([
        'tahun' => date('Y'),
        'no_rkm_medis' => $_POST['no_rkm_medis'],
        'diagnosa' => $_POST['diagnosa'],
        'terapi' => $_POST['terapi'],
        'alasan1' => $_POST['alasan1'],
        'alasan2' => '',
        'rtl1' => $_POST['rtl1'],
        'rtl2' => '',
        'tanggal_datang' => $_POST['tanggal_datang'],
        'tanggal_rujukan' => $_POST['tanggal_rujukan'],
        'no_antrian' => $this->core->setNoSKDP(),
        'kd_dokter' => $this->core->getRegPeriksaInfo('kd_dokter', $_POST['no_rawat']),
        'status' => 'Menunggu'
      ]);

      if ($query) {
        $this->db('booking_registrasi')
          ->save([
            'tanggal_booking' => date('Y-m-d'),
            'jam_booking' => date('H:i:s'),
            'no_rkm_medis' => $_POST['no_rkm_medis'],
            'tanggal_periksa' => $_POST['tanggal_datang'],
            'kd_dokter' => $this->core->getRegPeriksaInfo('kd_dokter', $_POST['no_rawat']),
            'kd_poli' => $this->core->getRegPeriksaInfo('kd_poli', $_POST['no_rawat']),
            'no_reg' => $this->core->setNoBooking($this->core->getRegPeriksaInfo('kd_dokter', $_POST['no_rawat']), $_POST['tanggal_datang'], $this->core->getRegPeriksaInfo('kd_poli', $_POST['no_rawat'])),
            'kd_pj' => $this->core->getRegPeriksaInfo('kd_pj', $_POST['no_rawat']),
            'limit_reg' => 0,
            'waktu_kunjungan' => $_POST['tanggal_datang'].' '.date('H:i:s'),
            'status' => 'Belum'
          ]);
      }

      exit();
    }

    public function postSaveKontrolBPJS()
    {

      date_default_timezone_set('UTC');
      $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
      $key = $this->consid . $this->secretkey . $tStamp;
      $_POST['sep_user']  = $this->core->getUserInfo('fullname', null, true);

      $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $this->core->getRegPeriksaInfo('kd_dokter', $_POST['no_rawat']))->oneArray();
      $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $this->core->getRegPeriksaInfo('kd_poli', $_POST['no_rawat']))->oneArray();
      $get_sep = $this->db('bridging_sep')->where('no_rawat', $_POST['no_rawat'])->oneArray();
      $_POST['no_sep'] = $get_sep['no_sep'];
      $get_sep_internal = $this->db('bridging_sep_internal')->where('no_rawat', $_POST['no_rawat'])->oneArray();

      if(empty($get_sep['no_sep'])) {
        $_POST['no_sep'] = $get_sep_internal['no_sep'];
      }

      $data = [
        'request' => [
          'noSEP' => $_POST['no_sep'],
          'kodeDokter' => $maping_dokter_dpjpvclaim['kd_dokter_bpjs'],
          'poliKontrol' => $maping_poli_bpjs['kd_poli_bpjs'],
          'tglRencanaKontrol' => $_POST['tanggal_datang'],
          'user' => $_POST['sep_user']
        ]
      ];
      $statusUrl = 'insert';
      $method = 'post';

      $data = json_encode($data);

      $url = $this->api_url . 'RencanaKontrol/insert';
      $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
      $data = json_decode($output, true);
      //echo $data['metaData']['message'];
      if ($data == NULL) {
        echo 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!';
      } else if ($data['metaData']['code'] == 200) {
        $stringDecrypt = stringDecrypt($key, $data['response']);
        $decompress = '""';
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
        $spri = json_decode($decompress, true);
        //echo $spri['noSuratKontrol'];

        $bridging_surat_pri_bpjs = $this->db('bridging_surat_kontrol_bpjs')->save([
          'no_sep' => $_POST['no_sep'],
          'tgl_surat' => $_POST['tanggal_rujukan'],
          'no_surat' => $spri['noSuratKontrol'],
          'tgl_rencana' => $_POST['tanggal_datang'],
          'kd_dokter_bpjs' => $maping_dokter_dpjpvclaim['kd_dokter_bpjs'],
          'nm_dokter_bpjs' => $maping_dokter_dpjpvclaim['nm_dokter_bpjs'],
          'kd_poli_bpjs' => $maping_poli_bpjs['kd_poli_bpjs'],
          'nm_poli_bpjs' => $maping_poli_bpjs['nm_poli_bpjs']
        ]);

      }

      exit();
    }

    public function postHapusKontrol()
    {
      $this->db('booking_registrasi')->where('kd_dokter', $_POST['kd_dokter'])->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('tanggal_periksa', $_POST['tanggal_periksa'])->where('status', 'Belum')->delete();
      $this->db('skdp_bpjs')->where('kd_dokter', $_POST['kd_dokter'])->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('tanggal_datang', $_POST['tanggal_periksa'])->where('status', 'Menunggu')->delete();
      exit();
    }

    public function getPersetujuanUmum($no_rkm_medis)
    {
      $settings = $this->settings('settings');
      $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
      $pasien = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
      echo $this->draw('persetujuan.umum.html', ['pasien' => $pasien]);
      exit();
    }

    public function postSaveICD10()
    {
      $_POST['status_penyakit'] = 'Baru';
      unset($_POST['nama']);
      $this->db('diagnosa_pasien')->save($_POST);
      exit();
    }  

    public function postHapusICD10()
    {
      $this->db('diagnosa_pasien')->where('no_rawat', $_POST['no_rawat'])->where('prioritas', $_POST['prioritas'])->delete();
      exit();
    }
  
    public function postICD10()
    {
  
      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('penyakit')->like('kd_penyakit', $key)->orLike('nm_penyakit', $key)->asc('kd_penyakit')->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.$row["kd_penyakit"].': '.$row["nm_penyakit"].'</li>';
          }
        } else {
          $output .= '<li class="list-group-item link-class">Tidak ada yang cocok.</li>';
        }
        echo $output;
      }
  
      exit();
  
    }

    public function postSaveICD9()
    {
      unset($_POST['nama']);
      $this->db('prosedur_pasien')->save($_POST);
      exit();
    }

    public function postHapusICD9()
    {
      $this->db('prosedur_pasien')->where('no_rawat', $_POST['no_rawat'])->where('prioritas', $_POST['prioritas'])->delete();
      exit();
    }

    public function postICD9()
    {
  
      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('icd9')->like('kode', $key)->orLike('deskripsi_panjang', $key)->asc('kode')->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.$row["kode"].': '.$row["deskripsi_panjang"].'</li>';
          }
        } else {
          $output .= '<li class="list-group-item link-class">Tidak ada yang cocok.</li>';
        }
        echo $output;
      }
  
      exit();
  
    }

    public function getDisplayICD()
    {
      $no_rawat = $_GET['no_rawat'];
      $prosedurs = $this->db('prosedur_pasien')
        ->where('no_rawat', $no_rawat)
        ->asc('prioritas')
        ->toArray();
      $prosedur = [];
      foreach ($prosedurs as $row_prosedur) {
        $icd9 = $this->db('icd9')->where('kode', $row_prosedur['kode'])->oneArray();
        $row_prosedur['nama'] = $icd9['deskripsi_panjang'];
        $prosedur[] = $row_prosedur;
      }
  
      $diagnosas = $this->db('diagnosa_pasien')
        ->where('no_rawat', $no_rawat)
        ->asc('prioritas')
        ->toArray();
      $diagnosa = [];
      foreach ($diagnosas as $row_diagnosa) {
        $icd10 = $this->db('penyakit')->where('kd_penyakit', $row_diagnosa['kd_penyakit'])->oneArray();
        $row_diagnosa['nama'] = $icd10['nm_penyakit'];
        $diagnosa[] = $row_diagnosa;
      }
  
      echo $this->draw('display.icd.html', ['diagnosa' => $diagnosa, 'prosedur' => $prosedur]);
      exit();
    }

    public function getAssessment($no_rawat)
    {
      $no_rawat = revertNoRawat($no_rawat);
      
      // Cek apakah sudah ada data assessment
      $penilaian_ranap = $this->db('mlite_penilaian_awal_keperawatan_ranap')
        ->where('no_rawat', $no_rawat)
        ->oneArray();
      
      // Jika belum ada, ambil data fallback dari pemeriksaan_ralan dan pemeriksaan_ranap
      if(!$penilaian_ranap) {
        $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')
          ->where('no_rawat', $no_rawat)
          ->desc('tgl_perawatan')
          ->desc('jam_rawat')
          ->oneArray();
          
        $pemeriksaan_ranap = $this->db('pemeriksaan_ranap')
          ->where('no_rawat', $no_rawat)
          ->desc('tgl_perawatan')
          ->desc('jam_rawat')
          ->oneArray();
        
        $penilaian_ranap = [
          'no_rawat' => $no_rawat,
          'tanggal' => date('Y-m-d H:i:s'),
          'informasi' => 'Autoanamnesis',
          'ket_informasi' => '',
          'tiba_diruang_rawat' => 'Jalan Tanpa Bantuan',
          'kasus_trauma' => 'Non Trauma',
          'cara_masuk' => 'Poli',
          'rps' => $pemeriksaan_ralan['keluhan'] ?? $pemeriksaan_ranap['keluhan'] ?? '',
          'rpd' => '',
          'rpk' => '',
          'rpo' => '',
          'riwayat_pembedahan' => '',
          'riwayat_dirawat_dirs' => '',
          'alat_bantu_dipakai' => 'Kacamata',
          'riwayat_kehamilan' => 'Tidak',
          'riwayat_kehamilan_perkiraan' => '',
          'riwayat_tranfusi' => '',
          'riwayat_alergi' => $pemeriksaan_ralan['alergi'] ?? $pemeriksaan_ranap['alergi'] ?? '',
          'riwayat_merokok' => 'Tidak',
          'riwayat_merokok_jumlah' => '',
          'riwayat_alkohol' => 'Tidak',
          'riwayat_alkohol_jumlah' => '',
          'riwayat_narkoba' => 'Tidak',
          'riwayat_olahraga' => 'Tidak',
          'pemeriksaan_mental' => '',
          'pemeriksaan_keadaan_umum' => 'Baik',
          'pemeriksaan_gcs' => $pemeriksaan_ralan['gcs'] ?? $pemeriksaan_ranap['gcs'] ?? '',
          'pemeriksaan_td' => $pemeriksaan_ralan['tensi'] ?? $pemeriksaan_ranap['tensi'] ?? '',
          'pemeriksaan_nadi' => $pemeriksaan_ralan['nadi'] ?? $pemeriksaan_ranap['nadi'] ?? '',
          'pemeriksaan_rr' => $pemeriksaan_ralan['respirasi'] ?? $pemeriksaan_ranap['respirasi'] ?? '',
          'pemeriksaan_suhu' => $pemeriksaan_ralan['suhu_tubuh'] ?? $pemeriksaan_ranap['suhu_tubuh'] ?? '',
          'pemeriksaan_spo2' => $pemeriksaan_ralan['spo2'] ?? $pemeriksaan_ranap['spo2'] ?? '',
          'pemeriksaan_bb' => $pemeriksaan_ralan['berat'] ?? $pemeriksaan_ranap['berat'] ?? '',
          'pemeriksaan_tb' => $pemeriksaan_ralan['tinggi'] ?? $pemeriksaan_ranap['tinggi'] ?? '',
          'pemeriksaan_susunan_kepala' => 'TAK',
          'pemeriksaan_susunan_wajah' => 'TAK',
          'pemeriksaan_susunan_leher' => 'TAK',
          'pemeriksaan_susunan_kejang' => 'TAK',
          'pemeriksaan_susunan_sensorik' => 'TAK',
          'pemeriksaan_kardiovaskuler_denyut_nadi' => 'Teratur',
          'pemeriksaan_kardiovaskuler_sirkulasi' => 'Akral Hangat',
          'pemeriksaan_kardiovaskuler_pulsasi' => 'Kuat',
          'pemeriksaan_respirasi_pola_nafas' => 'Normal',
          'pemeriksaan_respirasi_retraksi' => 'Tidak Ada',
          'pemeriksaan_respirasi_suara_nafas' => 'Vesikuler',
          'pemeriksaan_respirasi_volume_pernafasan' => 'Normal',
          'pemeriksaan_respirasi_jenis_pernafasan' => 'Pernafasan Dada',
          'pemeriksaan_respirasi_irama_nafas' => 'Teratur',
          'pemeriksaan_respirasi_batuk' => 'Tidak',
          'pemeriksaan_gastrointestinal_mulut' => 'TAK',
          'pemeriksaan_gastrointestinal_gigi' => 'TAK',
          'pemeriksaan_gastrointestinal_lidah' => 'TAK',
          'pemeriksaan_gastrointestinal_tenggorokan' => 'TAK',
          'pemeriksaan_gastrointestinal_abdomen' => 'Supel',
          'pemeriksaan_gastrointestinal_peistatik_usus' => 'TAK',
          'pemeriksaan_gastrointestinal_anus' => 'TAK',
          'pemeriksaan_neurologi_pengelihatan' => 'TAK',
          'pemeriksaan_neurologi_alat_bantu_penglihatan' => 'Tidak',
          'pemeriksaan_neurologi_pendengaran' => 'TAK',
          'pemeriksaan_neurologi_bicara' => 'Jelas',
          'pemeriksaan_neurologi_sensorik' => 'TAK',
          'pemeriksaan_neurologi_motorik' => 'TAK',
          'pemeriksaan_neurologi_kekuatan_otot' => 'Kuat',
          'pemeriksaan_integument_warnakulit' => 'Normal',
          'pemeriksaan_integument_turgor' => 'Baik',
          'pemeriksaan_integument_kulit' => 'Normal',
          'pemeriksaan_integument_dekubitas' => 'Tidak Ada',
          'pemeriksaan_muskuloskletal_pergerakan_sendi' => 'Bebas',
          'pemeriksaan_muskuloskletal_kekauatan_otot' => 'Baik',
          'pemeriksaan_muskuloskletal_nyeri_sendi' => 'Tidak Ada',
          'pemeriksaan_muskuloskletal_oedema' => 'Tidak Ada',
          'pemeriksaan_muskuloskletal_fraktur' => 'Tidak Ada',
          'pemeriksaan_eliminasi_bab_frekuensi_jumlah' => '',
          'pemeriksaan_eliminasi_bab_frekuensi_durasi' => '',
          'pemeriksaan_eliminasi_bab_konsistensi' => '',
          'pemeriksaan_eliminasi_bab_warna' => '',
          'pemeriksaan_eliminasi_bak_frekuensi_jumlah' => '',
          'pemeriksaan_eliminasi_bak_frekuensi_durasi' => '',
          'pemeriksaan_eliminasi_bak_warna' => '',
          'pemeriksaan_eliminasi_bak_lainlain' => '',
          'pola_aktifitas_makanminum' => 'Mandiri',
          'pola_aktifitas_mandi' => 'Mandiri',
          'pola_aktifitas_eliminasi' => 'Mandiri',
          'pola_aktifitas_berpakaian' => 'Mandiri',
          'pola_aktifitas_berpindah' => 'Mandiri',
          'pola_nutrisi_frekuesi_makan' => '',
          'pola_nutrisi_jenis_makanan' => '',
          'pola_nutrisi_porsi_makan' => '',
          'pola_tidur_lama_tidur' => '',
          'pola_tidur_gangguan' => 'Tidak Ada Gangguan',
          'pengkajian_fungsi_kemampuan_sehari' => 'Mandiri',
          'pengkajian_fungsi_aktifitas' => 'Berjalan',
          'pengkajian_fungsi_berjalan' => 'TAK',
          'pengkajian_fungsi_ambulasi' => 'Tidak Menggunakan',
          'pengkajian_fungsi_ekstrimitas_atas' => 'TAK',
          'pengkajian_fungsi_ekstrimitas_bawah' => 'TAK',
          'pengkajian_fungsi_menggenggam' => 'Tidak Ada Kesulitan',
          'pengkajian_fungsi_koordinasi' => 'Tidak Ada Kesulitan',
          'pengkajian_fungsi_kesimpulan' => 'Tidak (Tidak Perlu Co DPJP)',
          'riwayat_psiko_kondisi_psiko' => 'Tidak Ada Masalah',
          'riwayat_psiko_gangguan_jiwa' => 'Tidak',
          'riwayat_psiko_perilaku' => 'Tidak Ada Masalah',
          'riwayat_psiko_hubungan_keluarga' => 'Harmonis',
          'riwayat_psiko_tinggal' => 'Keluarga',
          'riwayat_psiko_nilai_kepercayaan' => 'Tidak Ada',
          'riwayat_psiko_pendidikan_pj' => '-',
          'riwayat_psiko_edukasi_diberikan' => 'Pasien',
          'penilaian_nyeri' => 'Tidak Ada Nyeri',
          'penilaian_nyeri_penyebab' => 'Proses Penyakit',
          'penilaian_nyeri_kualitas' => 'Seperti Tertusuk',
          'penilaian_nyeri_lokasi' => '',
          'penilaian_nyeri_menyebar' => 'Tidak',
          'penilaian_nyeri_skala' => '0',
          'penilaian_nyeri_waktu' => '',
          'penilaian_nyeri_hilang' => 'Istirahat',
          'penilaian_nyeri_diberitahukan_dokter' => 'Tidak',
          'penilaian_nyeri_jam_diberitahukan_dokter' => '',
          'penilaian_jatuhmorse_skala1' => 'Tidak',
          'penilaian_jatuhmorse_nilai1' => 0,
          'penilaian_jatuhmorse_skala2' => 'Tidak',
          'penilaian_jatuhmorse_nilai2' => 0,
          'penilaian_jatuhmorse_skala3' => 'Tidak Ada/Kursi Roda/Perawat/Tirah Baring',
          'penilaian_jatuhmorse_nilai3' => 0,
          'penilaian_jatuhmorse_skala4' => 'Tidak',
          'penilaian_jatuhmorse_nilai4' => 0,
          'penilaian_jatuhmorse_skala5' => 'Normal/Tirah Baring/Imobilisasi',
          'penilaian_jatuhmorse_nilai5' => 0,
          'penilaian_jatuhmorse_skala6' => 'Sadar Akan Kemampuan Diri Sendiri',
          'penilaian_jatuhmorse_nilai6' => 0,
          'penilaian_jatuhmorse_totalnilai' => 0,
          'penilaian_jatuhsydney_skala1' => 'Tidak',
          'penilaian_jatuhsydney_nilai1' => 0,
          'penilaian_jatuhsydney_skala2' => 'Tidak',
          'penilaian_jatuhsydney_nilai2' => 0,
          'penilaian_jatuhsydney_skala3' => 'Tidak',
          'penilaian_jatuhsydney_nilai3' => 0,
          'penilaian_jatuhsydney_skala4' => 'Tidak',
          'penilaian_jatuhsydney_nilai4' => 0,
          'penilaian_jatuhsydney_skala5' => 'Tidak',
          'penilaian_jatuhsydney_nilai5' => 0,
          'penilaian_jatuhsydney_skala6' => 'Tidak',
          'penilaian_jatuhsydney_nilai6' => 0,
          'penilaian_jatuhsydney_skala7' => 'Tidak',
          'penilaian_jatuhsydney_nilai7' => 0,
          'penilaian_jatuhsydney_skala8' => 'Tidak',
          'penilaian_jatuhsydney_nilai8' => 0,
          'penilaian_jatuhsydney_skala9' => 'Tidak',
          'penilaian_jatuhsydney_nilai9' => 0,
          'penilaian_jatuhsydney_skala10' => 'Tidak',
          'penilaian_jatuhsydney_nilai10' => 0,
          'penilaian_jatuhsydney_skala11' => 'Tidak',
          'penilaian_jatuhsydney_nilai11' => 0,
          'penilaian_jatuhsydney_totalnilai' => 0,
          'skrining_gizi1' => 'Tidak ada penurunan berat badan',
          'nilai_gizi1' => 0,
          'skrining_gizi2' => 'Tidak',
          'nilai_gizi2' => 0,
          'nilai_total_gizi' => 0,
          'skrining_gizi_diagnosa_khusus' => 'Tidak',
          'skrining_gizi_diketahui_dietisen' => 'Tidak',
          'skrining_gizi_jam_diketahui_dietisen' => '',
          'rencana' => '',
          'nip1' => $this->core->getUserInfo('username', null, true),
          'nip2' => $this->core->getUserInfo('username', null, true),
          'kd_dokter' => ''
        ];
      }
      
      echo $this->draw('assesment.html', ['penilaian_ranap' => $penilaian_ranap]);
      exit();
    }

    public function postAssessmentsave()
    {
      $_POST['nip1'] = $this->core->getUserInfo('username', null, true);
      $_POST['nip2'] = $this->core->getUserInfo('username', null, true);
      
      // Remove fields that don't exist in database
      $data = $_POST;
      unset($data['no_rawat_display']);
      
      // Cek apakah sudah ada data
      $existing = $this->db('mlite_penilaian_awal_keperawatan_ranap')
        ->where('no_rawat', $data['no_rawat'])
        ->oneArray();
      
      if($existing) {
        // Update data yang sudah ada
        $query = $this->db('mlite_penilaian_awal_keperawatan_ranap')
          ->where('no_rawat', $data['no_rawat'])
          ->save($data);
      } else {
        // Insert data baru
        $query = $this->db('mlite_penilaian_awal_keperawatan_ranap')->save($data);
      }
      
      if($query) {
        $data['status'] = 'success';
        echo json_encode($data);
      } else {
        $data['status'] = 'error';
        $data['msg'] = 'Gagal menyimpan data assessment';
        echo json_encode($data);
      }
      exit();
    }

    public function getAssessmenttampil($no_rawat)
    {
      $no_rawat = revertNoRawat($no_rawat);
      
      $penilaian_ranap = $this->db('mlite_penilaian_awal_keperawatan_ranap')
        ->join('petugas as p1', 'p1.nip=mlite_penilaian_awal_keperawatan_ranap.nip1')
        ->join('petugas as p2', 'p2.nip=mlite_penilaian_awal_keperawatan_ranap.nip2')
        ->where('no_rawat', $no_rawat)
        ->oneArray();
      
      if($penilaian_ranap) {
        $penilaian_ranap['nama_petugas1'] = $penilaian_ranap['p1.nama'];
        $penilaian_ranap['nama_petugas2'] = $penilaian_ranap['p2.nama'];
      }
      
      echo $this->draw('assesment.tampil.html', ['penilaian_ranap' => $penilaian_ranap]);
      exit();
    }

    public function postAssessmentdelete()
    {
      $query = $this->db('mlite_penilaian_awal_keperawatan_ranap')
        ->where('no_rawat', $_POST['no_rawat'])
        ->delete();
      
      if($query) {
        $data['status'] = 'success';
        echo json_encode($data);
      } else {
        $data['status'] = 'error';
        $data['msg'] = 'Gagal menghapus data assessment';
        echo json_encode($data);
      }
      exit();
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        $cek_pegawai = $this->db('pegawai')->where('nik', $this->core->getUserInfo('username', $_SESSION['mlite_user']))->oneArray();
        $cek_role = '';
        if($cek_pegawai) {
          $cek_role = $this->core->getPegawaiInfo('nik', $this->core->getUserInfo('username', $_SESSION['mlite_user']));
        }
        echo $this->draw(MODULES.'/rawat_inap/js/admin/rawat_inap.js', ['cek_role' => $cek_role]);
        exit();
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $this->core->addJS(url('assets/jscripts/lightbox/lightbox.min.js'));
        $this->core->addCSS(url('assets/jscripts/lightbox/lightbox.min.css'));
        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));
        $this->core->addJS(url([ADMIN, 'rawat_inap', 'javascript']), 'footer');
    }

}
