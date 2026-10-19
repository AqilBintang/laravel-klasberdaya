<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentIndicator;
use App\Models\AssessmentRubric;
use App\Models\RubricItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk panduan rubrik penilaian Kecamatan Berdaya periode 2026.
 * Data diambil dari panduan_rubrik.csv.
 *
 * Cara jalankan:
 *   php artisan db:seed --class=RubrikPanduan2026Seeder
 *
 * Idempotent: jika rubrik untuk assessment ini sudah ada, akan dihapus lalu dibuat ulang.
 */
class RubrikPanduan2026Seeder extends Seeder
{
    public function run(): void
    {
        // Cari assessment periode 2026
        $assessment = Assessment::where('periode', '2026')->first();

        if (!$assessment) {
            $this->command->error('Assessment periode 2026 tidak ditemukan. Jalankan seeder assessment terlebih dahulu.');
            return;
        }

        $this->command->info("Ditemukan assessment ID={$assessment->id} periode {$assessment->periode}: {$assessment->title}");

        // Data rubrik per (category_code => number => [score1, score2, score3, score4])
        $rubrikData = $this->rubrikData();

        DB::transaction(function () use ($assessment, $rubrikData) {
            // Hapus rubrik lama untuk assessment ini (idempotent)
            $oldRubricIds = AssessmentRubric::where('assessment_id', $assessment->id)->pluck('id');
            if ($oldRubricIds->isNotEmpty()) {
                RubricItem::whereIn('rubric_id', $oldRubricIds)->delete();
                AssessmentRubric::where('assessment_id', $assessment->id)->delete();
                $this->command->warn('Rubrik lama dihapus, membuat ulang...');
            }

            // Buat rubrik baru
            $rubric = AssessmentRubric::create([
                'assessment_id' => $assessment->id,
                'title'         => 'Rubrik Penilaian Kecamatan Berdaya 2026',
                'description'   => 'Panduan rubrik penilaian self assessment Kecamatan Berdaya Provinsi Jawa Tengah periode 2026.',
            ]);

            // Cache indikator aktif per (code, number) => indicator_id
            $indicators = AssessmentIndicator::where('assessment_id', $assessment->id)
                ->where('is_active', true)
                ->with('category:id,code')
                ->get();

            $indicatorMap = [];
            foreach ($indicators as $ind) {
                $code = $ind->category->code;
                $indicatorMap["{$code}:{$ind->number}"] = $ind->id;
            }

            $created = 0;
            $missing = [];
            foreach ($rubrikData as $code => $items) {
                foreach ($items as $number => $scores) {
                    $key = "{$code}:{$number}";
                    if (!isset($indicatorMap[$key])) {
                        $missing[] = $key;
                        continue;
                    }
                    RubricItem::create([
                        'rubric_id'    => $rubric->id,
                        'indicator_id' => $indicatorMap[$key],
                        'score1'       => $scores[0],
                        'score2'       => $scores[1],
                        'score3'       => $scores[2],
                        'score4'       => $scores[3],
                    ]);
                    $created++;
                }
            }

            $this->command->info("Rubrik dibuat: {$created} item.");
            if (!empty($missing)) {
                $this->command->warn('Indikator tidak ditemukan untuk key: ' . implode(', ', $missing));
            }
        });

        $this->command->info('Selesai. Rubrik panduan 2026 siap digunakan di halaman /kecamatan/panduan.');
    }

    /**
     * Data rubrik: score1 (skor 1), score2 (skor 2), score3 (skor 3), score4 (skor 4).
     * Struktur: [category_code => [indicator_number => [s1, s2, s3, s4]]]
     */
    private function rubrikData(): array
    {
        return [

            // ── A. Program Prioritas Rumah Perlindungan Perempuan dan Anak ──
            'A' => [
                1 => [
                    'Belum memiliki SK Tim RPPA Kecamatan',
                    'SK Tim RPPA telah ditetapkan, TAPI belum memiliki struktur dan pembagian peran yang jelas',
                    'SK Tim RPPA telah ditetapkan, memiliki struktur dan pembagian peran yang jelas, TAPI belum memuat unsur lintas sektor secara memadai',
                    'SK Tim RPPA telah ditetapkan, memiliki struktur dan pembagian peran yang jelas, serta memuat unsur lintas sektor (minimal: pemerintah kecamatan, layanan kesehatan, balai KB/satyagatra, kepolisian, unsur masyarakat)',
                ],
                2 => [
                    'Tim RPPA Kecamatan belum terbentuk atau sudah terbentuk tapi tidak aktif (tidak ada kegiatan dalam 6 bulan terakhir)',
                    'Tim sudah terbentuk TAPI hanya melakukan kegiatan terbatas (≤2 kegiatan dalam 6 bulan terakhir)',
                    'Tim melaksanakan kegiatan secara berkala (3-5 kegiatan dalam 6 bulan terakhir) dan melaksanakan fungsi layanan',
                    'Tim melaksanakan kegiatan secara berkala (≥ 6 kegiatan dalam 6 bulan terakhir dan terdapat kegiatan rutin per bulan), serta telah melaksanakan fungsi layanan (pengaduan, pendampingan, koordinasi, sosialisasi)',
                ],
                3 => [
                    'Belum tersedia sekretariat RPPA atau RPPA belum terbentuk di kecamatan',
                    'Sudah tersedia sekretariat RPPA, TAPI belum memiliki pembagian ruang yang jelas sesuai fungsi layanan; dan/atau sarana prasarana dasar layanan belum memadai',
                    'Sudah tersedia sekretariat RPPA dengan pembagian ruang yang jelas sesuai fungsi layanan (minimal ruang konseling dan ruang tunggu); sarana prasarana dasar tersedia; TAPI belum optimal dalam pemanfaatan atau penataan layanan',
                    'SUDAH tersedia sekretariat RPPA yang memiliki pembagian ruang sesuai standar layanan (ruang konseling, ruang tunggu, dan ruang pendukung); sarana prasarana memadai dan berfungsi; dimanfaatkan secara aktif untuk layanan perlindungan perempuan dan anak (dilihat dari data penerima manfaat layanan)',
                ],
                4 => [
                    'Belum dilakukan sosialisasi layanan RPPA atau RPPA belum terbentuk di kecamatan',
                    'Sosialisasi layanan RPPA belum dilaksanakan, namun telah terdapat rencana kerja/jadwal sosialisasi yang didukung dokumen/bukti sahih (misalnya: TOR, undangan, rencana kegiatan, daftar program kerja RPPA, rapat koordinasi persiapan)',
                    'Sosialisasi layanan RPPA telah dilaksanakan, namun masih terbatas pada sebagian wilayah atau kelompok sasaran (kurang dari 4 kelompok sasaran dalam 1 tahun terakhir)',
                    'Sosialisasi layanan RPPA telah dilaksanakan secara luas dan menjangkau berbagai kelompok sasaran masyarakat di wilayah kecamatan (lebih dari 5 kelompok sasaran dalam 1 tahun terakhir, dan terdapat perencanaan rutin tiap tahun)',
                ],
                5 => [
                    'Forum Anak Kecamatan belum terbentuk',
                    'Forum Anak Kecamatan sudah terbentuk tapi SK masih dalam tahap penyusunan/draf (dibuktikan dokumen pendukung)',
                    'SK telah ditetapkan namun belum disosialisasikan/ditindaklanjuti',
                    'SK telah ditetapkan dan disosialisasikan',
                ],
                6 => [
                    'Forum Anak Kecamatan belum melaksanakan kegiatan atau belum terbentuk',
                    'Forum Anak Kecamatan telah memiliki rencana kegiatan/program kerja namun belum terealisasi',
                    'Forum Anak Kecamatan telah melaksanakan kegiatan/program kerja namun belum rutin (kurang dari 2 kegiatan selama 6 bulan) dan kegiatannya belum dipublikasikan (melalui sosial media, platform Jogo Konco atau website forumanak.id)',
                    'Forum Anak Kecamatan telah melaksanakan kegiatan/program kerja secara rutin (lebih dari 3 kegiatan selama 6 bulan) dan pelaksanaannya sudah dipublikasikan (melalui sosial media, platform Jogo Konco atau website forumanak.id)',
                ],
                7 => [
                    'Forum Anak belum terlibat dalam kegiatan RPPA Kecamatan atau belum terbentuk',
                    'Forum Anak Kecamatan telah dilibatkan secara terbatas dalam kegiatan RPPA (hanya dilibatkan dalam rapat/koordinasi saja atau memiliki rencana kegiatan/program kerja bersama antara Forum Anak dan RPPA namun belum terealisasi)',
                    'Forum Anak sudah berperan sebagai pelopor atau pelapor dalam kegiatan perlindungan anak, TAPI belum terintegrasi dalam mekanisme layanan',
                    'Forum Anak berperan aktif sebagai pelopor dan pelapor (terlibat dalam kegiatan RPPA dan memiliki program kerja yang mendukung fungsi layanan RPPA) serta terintegrasi dalam mekanisme layanan RPPA',
                ],
                8 => [
                    'Forum GenRe / PIK Remaja belum terlibat dalam kegiatan terkait perlindungan perempuan dan anak atau belum terdapat Forum Genre di Kecamatan',
                    'Forum GenRe / PIK Remaja telah terlibat secara terbatas dalam kegiatan pencegahan atau edukasi terkait Perempuan dan Anak di Kecamatan, namun belum terintegrasi dengan RPPA',
                    'Forum GenRe telah terlibat dalam kegiatan pencegahan dan edukasi serta mulai berkoordinasi dengan RPPA (belum terdapat kegiatan kolaborasi yang dilakukan secara rutin)',
                    'Forum GenRe berperan aktif dan terintegrasi dalam kegiatan RPPA, khususnya dalam upaya pencegahan, edukasi, dan sosialisasi kepada remaja/masyarakat',
                ],
                9 => [
                    'Belum terdapat pendataan dan upaya fasilitasi kepemilikan dokumen adminduk bagi perempuan dan anak',
                    'Sudah terdapat data dan upaya dalam fasilitasi adminduk, namun belum terencana atau belum menjangkau kelompok rentan (<50% perempuan dan anak yang memiliki dokumen adminduk lengkap)',
                    'Fasilitasi adminduk telah dilakukan secara terencana dan menjangkau sebagian perempuan dan anak, termasuk kelompok rentan (50% s.d. 80% perempuan dan anak telah memiliki dokumen adminduk yang lengkap)',
                    'Fasilitasi adminduk dilakukan secara aktif, terencana, dan menjangkau sebagian besar perempuan dan anak, termasuk kelompok rentan, serta terintegrasi dengan layanan RPPA (>80% perempuan dan anak telah memiliki dokumen adminduk yang lengkap)',
                ],
                10 => [
                    'PPEP belum menjangkau kelompok/komunitas perempuan',
                    'PPEP menjangkau <25% kelompok/komunitas perempuan',
                    'PPEP menjangkau ≥25% s.d. <50% kelompok/komunitas perempuan',
                    'PPEP menjangkau ≥50% kelompok/komunitas perempuan',
                ],
                11 => [
                    'UPPKA belum menjangkau kelompok/keluarga',
                    'UPPKA menjangkau <25% kelompok/keluarga',
                    'UPPKA menjangkau ≥25% s.d. <50% kelompok/keluarga',
                    'UPPKA menjangkau ≥50% kelompok/keluarga',
                ],
                12 => [
                    'Belum terdapat kader RPPA atau kecamatan belum memiliki RPPA',
                    'Sudah terdapat kader RPPA, namun jumlahnya terbatas dan belum terlatih',
                    'Kader RPPA telah terlatih dan tersedia di sebagian wilayah desa/kelurahan, namun jumlahnya belum mencukupi',
                    'Kader RPPA tersedia secara memadai, terlatih, aktif, dan menjangkau seluruh desa/kelurahan di wilayah kecamatan',
                ],
                13 => [
                    'SOP alur pengaduan dan layanan belum tersedia ataupun kecamatan belum memiliki RPPA',
                    'SOP alur pengaduan dan layanan belum tersedia namun sudah dipersiapkan dan dibuktikan dengan bukti yang sahih',
                    'SOP alur pengaduan dan layanan tersedia namun belum lengkap',
                    'SOP alur pengaduan dan layanan tersedia dengan lengkap',
                ],
                14 => [
                    'Belum tersedia mekanisme pengaduan atau kecamatan belum memiliki RPPA',
                    'Mekanisme pengaduan sudah direncanakan/dipersiapkan namun belum berjalan',
                    'Mekanisme pengaduan telah tersedia namun belum berjalan secara optimal/aktif',
                    'Mekanisme pengaduan tersedia, aktif, dan mudah diakses oleh masyarakat serta terintegrasi dengan layanan RPPA',
                ],
                15 => [
                    'Koordinasi lintas sektor belum pernah dilaksanakan atau kecamatan belum memiliki RPPA',
                    'Koordinasi lintas sektor telah direncanakan namun belum terlaksana',
                    'Koordinasi lintas sektor telah dilaksanakan namun belum rutin atau belum melibatkan seluruh pihak terkait',
                    'Koordinasi lintas sektor dilaksanakan secara rutin, melibatkan berbagai pihak terkait, dan mendukung pelaksanaan layanan RPPA',
                ],
                16 => [
                    'Layanan penanganan kasus belum tersedia atau kecamatan belum memiliki RPPA',
                    'Layanan penanganan kasus sudah dipersiapkan namun belum berjalan',
                    'Layanan penanganan kasus telah berjalan, namun belum mencakup seluruh tahapan layanan',
                    'Layanan penanganan kasus berjalan dengan lengkap, mencakup identifikasi kebutuhan, pendampingan awal, dan rujukan sesuai kebutuhan',
                ],
                17 => [
                    'Belum terdapat koordinasi atau komunikasi dengan UPTD PPA dan layanan SAPA 129',
                    'Sudah terdapat komunikasi awal atau koordinasi terbatas dengan UPTD PPA atau layanan SAPA 129, namun belum pernah melakukan konsultasi/koordinasi/rujukan kasus',
                    'Sudah melakukan koordinasi dan rujukan kasus kepada UPTD PPA atau layanan SAPA 129, namun belum dilakukan secara konsisten/rutin berkoordinasi',
                    'Secara aktif melakukan koordinasi dan rujukan kasus kepada UPTD PPA dan/atau layanan SAPA 129 secara konsisten serta terdokumentasi',
                ],
                18 => [
                    'Tidak ada kegiatan penyuluhan, sosialisasi dan edukasi pencegahan kekerasan ataupun kecamatan belum memiliki RPPA',
                    'Kegiatan penyuluhan, sosialisasi dan edukasi pencegahan kekerasan belum terlaksana namun sudah direncanakan dan dibuktikan dengan bukti yang sahih',
                    'Kegiatan penyuluhan, sosialisasi dan edukasi pencegahan kekerasan terlaksana namun belum rutin',
                    'Kegiatan penyuluhan, sosialisasi dan edukasi pencegahan kekerasan terlaksana dan rutin',
                ],
                19 => [
                    'Belum terdapat pelibatan masyarakat dalam kegiatan RPPA atau kecamatan belum memiliki RPPA',
                    'Kegiatan telah direncanakan namun belum melibatkan masyarakat secara langsung',
                    'Masyarakat telah dilibatkan dalam kegiatan RPPA, namun masih terbatas pada kelompok tertentu',
                    'Masyarakat terlibat aktif dalam kegiatan RPPA dan terdapat bukti pemanfaatan layanan oleh masyarakat',
                ],
                20 => [
                    'Belum memiliki SK forum GenRe/ PIK Remaja',
                    'SK forum GenRe/PIK Remaja masih dalam tahap penyusunan/draf dengan bukti yang sahih',
                    'SK forum GenRe/PIK Remaja telah ditetapkan namun belum beraktivitas',
                    'SK forum GenRe/PIK Remaja telah ditetapkan dan telah beraktivitas secara rutin',
                ],
                21 => [
                    'Satyagatra belum aktif melakukan Kegiatan Pencegahan, konseling keluarga dan Penanganan Kekerasan',
                    'Satyagatra melakukan kegiatan pencegahan kekerasan saja',
                    'Satyagatra aktif melakukan kegiatan pencegahan kekerasan dan konseling keluarga',
                    'Satyagatra aktif melakukan kegiatan pencegahan kekerasan, konseling keluarga serta penanganan kekerasan',
                ],
            ],

            // ── B. Program Prioritas Perlindungan Lansia dan Disabilitas ──
            'B' => [
                1 => [
                    'Data lansia dan disabilitas tidak tersedia',
                    'Salah satu data baik lansia ataupun disabilitas tersedia namun belum ada pemutakhiran data',
                    'Data lansia dan disabilitas tersedia namun belum ada pemutakhiran data',
                    'Data lansia dan disabilitas tersedia dan termutakhir',
                ],
                2 => [
                    'Tidak ada Puskesos SLRT di desa/kelurahan',
                    'Puskesos SLRT aktif di <25% dari keseluruhan desa/kelurahan di kecamatan berdaya',
                    'Puskesos SLRT aktif di ≥25% s.d. 50% namun belum keseluruhan desa/kelurahan di kecamatan berdaya',
                    'PUSKESOS SLRT aktif >50% di semua desa/kelurahan di kecamatan berdaya',
                ],
                3 => [
                    'Tidak ada sarana prasarana tersedia',
                    'Sarana prasarana tersedia namun <25% dari keseluruhan puskesos SLRT di desa/kelurahan di kecamatan berdaya',
                    'Sarana prasarana tersedia namun ≥25% s.d. 50%, di desa/kelurahan di kecamatan berdaya',
                    'Sarana prasarana tersedia di seluruh PUSKESOS SLRT >50% di semua desa/kelurahan di kecamatan berdaya',
                ],
                4 => [
                    'TKSK/PSM/Fasilitator belum tersedia di kecamatan berdaya tersebut dan belum direncanakan pembentukannya',
                    'TKSK/PSM/Fasilitator belum tersedia di kecamatan berdaya tersebut namun sudah direncanakan pembentukannya',
                    'TKSK/PSM tersedia di kecamatan berdaya tersebut namun belum aktif',
                    'TKSK/PSM/Fasilitator tersedia di kecamatan berdaya tersebut dan aktif',
                ],
                5 => [
                    'Fasilitasi akses bansos belum berjalan seluruhnya di kecamatan dan belum direncanakan',
                    'Fasilitasi akses bansos belum berjalan seluruhnya di kecamatan namun sudah direncanakan dengan bukti yang sahih',
                    'Fasilitasi akses bansos hanya berjalan 1 atau 2 program di kecamatan',
                    'Fasilitasi akses bansos berjalan seluruhnya di kecamatan',
                ],
                6 => [
                    'Screening kesehatan lansia dan penyandang disabilitas belum dilaksanakan',
                    'Screening kesehatan lansia dan penyandang disabilitas belum dilaksanakan secara rutin dan masih bersifat insidental',
                    'Screening kesehatan lansia dan penyandang disabilitas dilaksanakan secara rutin namun lebih dari satu bulan sekali',
                    'Screening kesehatan lansia dan penyandang disabilitas minimal satu kali dalam sebulan',
                ],
                7 => [
                    'Program Speling belum dapat diakses oleh masyarakat',
                    'Program Speling baru dapat diakses <50% oleh masyarakat',
                    'Program Speling baru dapat diakses ≥50% s.d. <90% oleh masyarakat',
                    'Program Speling diakses ≥90% s.d. 100% oleh seluruh masyarakat',
                ],
                8 => [
                    'Kasus rujukan PUSKESOS SLRT tidak ada yang dapat diselesaikan',
                    'Kasus rujukan PUSKESOS diselesaikan <25%',
                    'Kasus rujukan PUSKESOS SLRT diselesaikan ≥25% s.d. <50%',
                    'Kasus rujukan PUSKESOS SLRT diselesaikan ≥50%',
                ],
                9 => [
                    'Alat bantu disabilitas belum terdistribusi',
                    'Alat bantu disabilitas terdistribusi <50% sesuai kebutuhan',
                    'Alat bantu disabilitas terdistribusi ≥50% s.d. 90% sesuai kebutuhan',
                    'Alat bantu disabilitas terdistribusi 91% s.d. 100% sesuai kebutuhan',
                ],
                10 => [
                    'Adminduk lansia/disabilitas tidak terfasilitasi',
                    'Adminduk lansia/disabilitas terfasilitasi <50%',
                    'Adminduk lansia/disabilitas terfasilitasi ≥50% s.d. 90% sesuai kebutuhan',
                    'Adminduk lansia/disabilitas terfasilitasi 91% s.d. 100% sesuai kebutuhan',
                ],
                11 => [
                    'Pelatihan keterampilan usaha keterampilan belum terlaksana',
                    'Pelatihan keterampilan usaha disabilitas belum terlaksana namun sudah direncanakan dan dibuktikan dengan bukti yang sahih',
                    'Pelatihan keterampilan usaha disabilitas terlaksana namun belum rutin',
                    'Pelatihan keterampilan usaha disabilitas terlaksana dan rutin',
                ],
                12 => [
                    'Penerima manfaat tidak ada yang menyatakan puas',
                    'Penerima manfaat menyatakan puas <35%',
                    'Penerima manfaat menyatakan puas ≥35% s.d. <70%',
                    'Penerima manfaat menyatakan puas >70%',
                ],
                13 => [
                    'Tidak ada komunitas inklusi di kecamatan berdaya',
                    'Komunitas inklusi disabilitas dan lansia di kecamatan berdaya sedang dalam proses pembentukan dibuktikan dengan bukti yang sahih',
                    'Komunitas inklusi disabilitas dan lansia di kecamatan berdaya sudah terbentuk namun belum ada aktivitas',
                    'Minimum 1 komunitas inklusi disabilitas dan lansia di kecamatan berdaya sudah terbentuk dan beraktivitas secara rutin',
                ],
                14 => [
                    'Tidak ada kegiatan penjangkauan penyandang disabilitas',
                    'Kegiatan penjangkauan penyandang disabilitas sudah direncanakan dengan bukti sahih',
                    'Kegiatan penjangkauan penyandang disabilitas sudah terlaksana namun belum kontinyu',
                    'Kegiatan penjangkauan penyandang disabilitas sudah terlaksana dan sudah kontinyu',
                ],
                15 => [
                    'Tidak ada kegiatan pendampingan disabilitas dan lansia',
                    'Kegiatan pendampingan disabilitas dan lansia sudah direncanakan dengan bukti sahih',
                    'Kegiatan pendampingan disabilitas dan lansia sudah terlaksana namun belum kontinyu',
                    'Kegiatan pendampingan disabilitas dan lansia sudah terlaksana dan sudah kontinyu',
                ],
                16 => [
                    'Tidak ada kolaborasi antar kelembagaan perlindungan lansia dan disabilitas',
                    'Kolaborasi antar kelembagaan perlindungan lansia dan disabilitas sudah direncanakan dengan bukti yang sahih',
                    'Kolaborasi antar kelembagaan perlindungan lansia dan disabilitas sudah dilakukan namun belum dilakukan secara rutin',
                    'Kolaborasi antar kelembagaan perlindungan lansia dan disabilitas sudah dilakukan dan sudah dilakukan secara rutin',
                ],
            ],

            // ── C. Program Prioritas Taruna Karya Mandiri (ZIlenial) ──
            'C' => [
                1 => [
                    'Tidak ada data pemuda sama sekali',
                    'Data ada, belum terklasifikasi, belum divalidasi',
                    'Data terklasifikasi sebagian, belum mencakup seluruh desa',
                    'Data lengkap dari seluruh desa, terklasifikasi penuh (pendidikan, pekerjaan, keterampilan), diperbarui rutin, digunakan sebagai dasar perencanaan.',
                ],
                2 => [
                    'Tidak ada sosialisasi; tidak ada Zilenial yang mendaftar.',
                    'Sosialisasi sudah dilakukan; pendaftar aktif < 25 akun.',
                    '25–49 akun aktif terdaftar; sebagian memanfaatkan fitur kartu digital.',
                    '≥ 50 akun aktif; pemegang kartu mengakses manfaat (pelatihan, mentoring, pitching, dsb). Kartu zilenial bisa diakses melalui website',
                ],
                3 => [
                    'Tidak ada kegiatan pelatihan yang terselenggara.',
                    'Pelatihan vokasi/kewirausahaan belum dilaksanakan namun sudah direncanakan dan dibuktikan dengan bukti yang sahih',
                    'Pelatihan vokasi/kewirausahaan sudah dilaksanakan namun belum rutin',
                    'Pelatihan vokasi/kewirausahaan sudah dilaksanakan dan rutin',
                ],
                4 => [
                    'Zilenial aktif bermedia sosial, namun seluruh sampel (50 orang) ditemukan melakukan penyebaran hoax, hingga menyebarkan ujaran kebencian',
                    'Zilenial aktif bermedia sosial, 50% (25 orang) sampel menggunakan ruang digital untuk kebutuhan pengembangan diri, dan hiburan yang sehat, 50% (25 orang) sampel lainnya masih ditemukan melakukan penyebaran atau mengakses konten hoax, hingga penyebaran ujaran kebencian',
                    'Zilenial aktif bermedia sosial, 80% (40 orang) sampel menggunakan ruang digital untuk kebutuhan pengembangan diri, dan hiburan yang sehat, 30% (10 orang) sampel lainnya masih ditemukan melakukan penyebaran atau mengakses konten hoax, hingga penyebaran ujaran kebencian',
                    'Zilenial aktif bermedia sosial, 100% (50 orang) sampel menggunakan ruang digital untuk kebutuhan pengembangan diri, dan hiburan yang sehat',
                ],
                5 => [
                    'Tidak ada komunitas Zilenial aktif; tidak ada forum kepemudaan.',
                    'Ada komunitas baru terbentuk; < 10 anggota; belum rutin berkegiatan.',
                    '≥ 1 komunitas aktif; ≥ 10 anggota; kegiatan rutin; diakui kecamatan.',
                    '≥ 1 komunitas aktif; ≥ 20 anggota; pernah tampil/berkontribusi di ruang publik; mendapat dukungan kecamatan.',
                ],
            ],

            // ── D. Program Prioritas Sport Center ──
            'D' => [
                1 => [
                    'Hanya terdapat 1 buah fasilitas olahraga, namun kondisinya kurang layak pakai (rusak/terbengkalai) atau aksesnya sangat terbatas bagi warga umum.',
                    'Hanya Terdapat minimal 1 fasilitas olahraga dalam kondisi layak pakai, sudah digunakan warga secara rutin, namun sarana pendukung (lampu, kebersihan, alat) terbatas',
                    'Terdapat >1 jenis fasilitas olahraga yang terawat dengan baik. Memiliki jadwal penggunaan yang jelas dan didukung sarana penunjang (toilet, tempat sampah, atau parkir).',
                    'Memiliki kompleks olahraga (Sport Center) atau berbagai fasilitas yang sangat layak. Pengelolaannya profesional/mandiri oleh komunitas/desa dan menjadi pusat kegiatan warga setiap hari.',
                ],
                2 => [
                    'Penggunaan fasilitas bersifat bebas/tanpa aturan. Tidak ada papan jadwal atau informasi tertulis, sehingga sering terjadi tabrakan penggunaan antar kelompok warga.',
                    'Jadwal penggunaan sudah ada namun hanya dipahami oleh segelintir pengurus atau melalui kesepakatan lisan. Belum ada informasi visual yang bisa diakses publik secara luas.',
                    'Jadwal penggunaan telah tertulis rapi dan dipasang di lokasi (papan pengumuman/spanduk). Pembagian waktu sudah mencakup berbagai kelompok (usia dini, umum, lansia).',
                    'Jadwal sudah tersusun matang dan dapat diakses/dipesan melalui platform digital (WhatsApp Group/Aplikasi/Sosmed). Ada sistem pelaporan penggunaan dan pembagian porsi waktu yang adil bagi seluruh lapisan masyarakat.',
                ],
                3 => [
                    'Tersedia <3 unit atau kondisi rusak/tidak layak.',
                    'Tersedia min. 3 unit, namun pemeliharaan belum rutin.',
                    'Tersedia >3 unit, kondisi layak, dan akses terbuka 24 jam.',
                    'Tersedia paket alat lengkap, terawat, dan ada instruksi penggunaan (papan/QR Code).',
                ],
                4 => [
                    'Belum ada pelatih yang bersertifikat resmi.',
                    'Ada 1 pelatih bersertifikat, namun hanya aktif secara insidental.',
                    'Ada min. 1 pelatih bersertifikat yang memimpin jadwal rutin warga.',
                    'Ada jejaring pelatih bersertifikat (berbagai cabang) dan melakukan kaderisasi.',
                ],
                5 => [
                    'Dilakukan <2x dalam setahun (hanya hari besar).',
                    'Dilakukan 2-3x setahun, partisipasi warga masih terbatas.',
                    'Dilakukan rutin min. 4x/tahun dengan partisipasi massa yang besar.',
                    'Dilakukan >4x/tahun, terjadwal tetap, dan menarik sponsor/pihak swasta.',
                ],
                6 => [
                    'Belum ada komunitas yang terorganisir secara resmi.',
                    'Ada 1-2 komunitas, namun kegiatan belum konsisten setiap bulan.',
                    'Ada min. 2 komunitas aktif dengan jadwal latihan rutin mingguan.',
                    'Ada >3 komunitas lintas cabang yang berkolaborasi dalam satu wadah.',
                ],
                7 => [
                    'Tidak ada turnamen dalam satu tahun terakhir.',
                    'Ada 1 turnamen kecil, namun manajemen kepanitiaan belum rapi.',
                    'Terselenggara min. 1x turnamen/tahun dengan manajemen yang baik.',
                    'Terselenggara rutin, melibatkan peserta luar wilayah, dan berdampak pada UMKM lokal.',
                ],
                8 => [
                    'Belum ada warga/kader yang dilatih secara formal.',
                    'Sudah ada <5 orang yang dilatih, namun belum praktik di lapangan.',
                    'Pelatihan dilakukan untuk min. 5 orang/tahun dan aktif memimpin laga.',
                    'Pelatihan >5 orang/tahun dengan sertifikasi yang diakui tingkat Kota/Provinsi.',
                ],
                9 => [
                    'Belum ada fasilitas atau program khusus disabilitas.',
                    'Fasilitas ada namun belum ramah disabilitas; program masih rencana.',
                    'Fasilitas memadai dan ada min. 1 sesi/program rutin bagi disabilitas.',
                    'Menjadi pusat olahraga inklusif dengan fasilitas standar dan pelatih khusus.',
                ],
                10 => [
                    'Partisipasi warga stagnan atau menurun dibanding tahun lalu.',
                    'Partisipasi meningkat namun masih di bawah 10%.',
                    'Partisipasi meningkat tepat 10% dari basis data sebelumnya.',
                    'Partisipasi meningkat drastis (>10%) dan terdokumentasi dengan data by name by address.',
                ],
                11 => [
                    'Data olahraga belum tercatat atau belum pernah dilaporkan.',
                    'Data ada namun tidak sinkron dengan format Indeks Pembangunan Olahraga (IPO).',
                    'Data tersusun rapi dan rutin dilaporkan ke tingkat Kabupaten/Kota.',
                    'Data sangat akurat, terdigitalisasi, dan menjadi acuan utama peningkatan IPO di level Provinsi.',
                ],
            ],

            // ── E. Administratif ──
            'E' => [
                1 => [
                    'SK tim kecamatan berdaya belum ada',
                    'SK Tim kecamatan berdaya sudah dalam proses dan dibuktikan dengan bukti yang sahih',
                    'SK Tim kecamatan berdaya sudah ditetapkan namun belum mulai beraktivitas',
                    'SK Tim kecamatan berdaya sudah ditetapkan dan sudah Beraktivitas',
                ],
                2 => [
                    'SK lokasi kecamatan berdaya belum ada',
                    'SK lokasi kecamatan berdaya sudah dalam proses dan dibuktikan dengan bukti yang sahih',
                    'SK lokasi kecamatan berdaya sudah ditetapkan, sudah siap dipakai namun belum digunakan',
                    'SK lokasi kecamatan berdaya sudah ditetapkan, sudah siap dipakai dan sudah digunakan',
                ],
            ],

            // ── F. Publikasi ──
            'F' => [
                1 => [
                    'Belum Ada Label/Plang/Penanda Setiap Layanan Kecamatan Berdaya',
                    'Label/Plang/Penanda Layanan Kecamatan Berdaya Terpasang Namun <50% dari keseluruhan',
                    'Label/Plang/Penanda Layanan Kecamatan Berdaya Terpasang 50% s.d. 75% dari keseluruhan',
                    'Label/Plang/Penanda Layanan Kecamatan Berdaya Terpasang >75% dari keseluruhan',
                ],
                2 => [
                    'Belum ada Publikasi Media Sosial',
                    'Publikasi Media Sosial sudah ada namun <50% dari keseluruhan desa/kelurahan kecamatan berdaya',
                    'Publikasi Media Sosial sudah ada 50% s.d. 75% dari keseluruhan desa/kelurahan kecamatan berdaya',
                    'Publikasi Media Sosial sudah ada >75% dari keseluruhan desa/kelurahan kecamatan berdaya',
                ],
                3 => [
                    'Belum ada Publikasi Media Cetak',
                    'Publikasi Media Cetak sudah ada namun <50% dari keseluruhan desa/kelurahan kecamatan berdaya',
                    'Publikasi Media Cetak sudah ada 50% s.d. 75% dari keseluruhan desa/kelurahan kecamatan berdaya',
                    'Publikasi Media Cetak sudah ada >75% dari keseluruhan desa/kelurahan kecamatan berdaya',
                ],
                4 => [
                    'Belum ada Publikasi Praktik Baik',
                    'Publikasi Praktik Baik sudah ada namun <50% dari keseluruhan desa/kelurahan kecamatan berdaya',
                    'Publikasi Praktik Baik sudah ada 50% s.d. 75% dari keseluruhan desa/kelurahan kecamatan berdaya',
                    'Publikasi Praktik Baik sudah ada >75% dari keseluruhan desa/kelurahan kecamatan berdaya',
                ],
                5 => [
                    'Belum ada Sosialisasi langsung layanan kecamatan berdaya',
                    'Sosialisasi langsung layanan kecamatan berdaya sudah ada namun <50% dari keseluruhan desa/kelurahan kecamatan berdaya',
                    'Sosialisasi langsung layanan kecamatan berdaya sudah ada 50% s.d. 75% dari keseluruhan desa/kelurahan kecamatan berdaya',
                    'Sosialisasi langsung layanan kecamatan berdaya sudah ada >75% dari keseluruhan desa/kelurahan kecamatan berdaya',
                ],
                6 => [
                    'Belum ada aktivitas publikasi partisipatif masyarakat',
                    'Aktivitas publikasi partisipatif masyarakat sudah ada namun <50% dari keseluruhan desa/kelurahan kecamatan berdaya',
                    'Aktivitas publikasi partisipatif masyarakat sudah ada 50% s.d. 75% dari keseluruhan desa/kelurahan kecamatan berdaya',
                    'Aktivitas publikasi masyarakat sudah ada >75% dari keseluruhan desa/kelurahan kecamatan berdaya',
                ],
            ],
        ];
    }
}
