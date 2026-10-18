<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use App\Models\Pengembalian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // Menampilkan Dashboard Admin & Log Aktivitas
    public function index()
    {
        $logs = LogAktivitas::with('user')->latest()->take(10)->get();
        return view('admin.dashboard', compact('logs'));
    }

    // CRUD Alat: Menampilkan daftar alat
    public function indexAlat(Request $request)
    {
        $search = $request->input('search');

        $alats = Alat::with('kategori')
            ->when($search, function ($query, $search) {
                return $query->where('nama_alat', 'like', "%{$search}%")
                    ->orWhere('status_kondisi', 'like', "%{$search}%")
                    ->orWhereHas('kategori', function ($q) use ($search) {
                        $q->where('nama_kategori', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view('admin.alat.index', compact('alats', 'search'));
    }

    // 2. Menampilkan form tambah alat
    public function createAlat()
    {
        $kategori = Kategori::all();
        return view('admin.alat.create', compact('kategori'));
    }

    // 3. Menyimpan alat baru
    public function storeAlat(Request $request)
    {
        $request->validate([
            'nama_alat'      => 'required|string|max:255',
            'kategori_id'    => 'required|exists:kategori,id',
            'stok'           => 'required|integer|min:0',
            'status_kondisi' => 'required|string|max:100',
            'deskripsi'      => 'nullable|string',
            'gambar'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();

        // Handle Upload Gambar jika ada
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('storage/alat'), $filename);
            $data['gambar'] = 'storage/alat/' . $filename;
        }

        Alat::create($data);

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aktivitas' => 'Menambahkan alat baru: ' . $request->nama_alat
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil ditambahkan.');
    }

    // 4. Menampilkan form edit alat
    public function editAlat($id)
    {
        $alat = Alat::findOrFail($id);
        $kategori = Kategori::all();
        return view('admin.alat.edit', compact('alat', 'kategori'));
    }

    // 5. Memperbarui data alat
    public function updateAlat(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'nama_alat'      => 'required|string|max:255',
            'kategori_id'    => 'required|exists:kategori,id',
            'stok'           => 'required|integer|min:0',
            'status_kondisi' => 'required|string|max:100',
            'deskripsi'      => 'nullable|string',
            'gambar'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();

        // Handle Update Gambar jika ada file baru
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($alat->gambar && file_exists(public_path($alat->gambar))) {
                unlink(public_path($alat->gambar));
            }

            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('storage/alat'), $filename);
            $data['gambar'] = 'storage/alat/' . $filename;
        }

        $alat->update($data);

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aktivitas' => 'Memperbarui data alat: ' . $alat->nama_alat
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil diperbarui.');
    }

    // 6. Menghapus data alat
    public function destroyAlat($id)
    {
        $alat = Alat::findOrFail($id);
        $namaAlat = $alat->nama_alat;

        // Hapus file gambar fisik jika ada
        if ($alat->gambar && file_exists(public_path($alat->gambar))) {
            unlink(public_path($alat->gambar));
        }

        $alat->delete();

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aktivitas' => 'Menghapus data alat: ' . $namaAlat
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil dihapus.');
    }

    // CRUD User (Manajemen User Admin, Petugas, Peminjam)
    public function indexUser(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('role', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.user.index', compact('users', 'search'));
    }

    public function createUser()
    {
        return view('admin.user.create');
    }

    // Menyimpan user baru ke database
    public function storeUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,petugas,peminjam',
            'no_hp'    => 'nullable|string|max:20',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'no_hp'    => $request->no_hp,
        ]);

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aktivitas' => 'Menambahkan user baru: ' . $request->name . ' (' . $request->role . ')'
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.user.edit', compact('user'));
    }

    // Memperbarui data user
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role'  => 'required|in:admin,petugas,peminjam',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
            'role'  => $request->role,
            'no_hp' => $request->no_hp,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aktivitas' => 'Memperbarui data user: ' . $user->name
        ]);

        return redirect()->route('admin.user.index')->with('success', 'Data user berhasil diperbarui.');
    }

    // Menghapus user
    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        $namaUser = $user->name;

        $user->delete();

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aktivitas' => 'Menghapus user: ' . $namaUser
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil dihapus.');
    }

    public function indexKategori(Request $request)
    {
        $search = $request->input('search');

        $kategori = Kategori::when($search, function ($query, $search) {
            return $query->where('nama_kategori', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.kategori.index', compact('kategori', 'search'));
    }

    // 2. Menampilkan form tambah kategori
    public function createKategori()
    {
        return view('admin.kategori.create');
    }

    // 3. Menyimpan kategori baru
    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
        ]);

        Kategori::create([
            'nama_kategori' => $request->nama_kategori,
        ]);

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aktivitas' => 'Menambahkan kategori baru: ' . $request->nama_kategori
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    // 4. Menampilkan form edit kategori
    public function editKategori($id)
    {
        $kategori = Kategori::findOrFail($id);
        return view('admin.kategori.edit', compact('kategori'));
    }

    // 5. Memperbarui kategori
    public function updateKategori(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori,' . $id,
        ]);

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
        ]);

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aktivitas' => 'Memperbarui kategori: ' . $kategori->nama_kategori
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    // 6. Menghapus kategori
    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);
        $namaKategori = $kategori->nama_kategori;

        if ($kategori->alats()->count() > 0) {
            return redirect()->route('admin.kategori.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh data alat.');
        }

        $kategori->delete();

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aktivitas' => 'Menghapus kategori: ' . $namaKategori
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }

    // 1. Menampilkan daftar peminjaman
    public function indexPeminjaman(Request $request)
    {
        $search = $request->input('search');

        // PERBAIKAN: detailPinjam diubah ke detailPinjams
        $peminjamans = Peminjaman::with(['user', 'detailPinjams.alat'])
            ->when($search, function ($query, $search) {
                return $query->where('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view('petugas.peminjaman.index', compact('peminjamans', 'search'));
    }

    // 2. Menampilkan form tambah peminjaman
    public function createPeminjaman()
    {
        $users = User::where('role', 'peminjam')->get();
        $alats = Alat::where('stok', '>', 0)->get();
        return view('admin.peminjaman.create', compact('users', 'alats'));
    }

    // 3. Menyimpan data peminjaman baru
    public function storePeminjaman(Request $request)
    {
        $request->validate([
            'user_id'          => 'required|exists:users,id',
            'tgl_pinjam'       => 'required|date',
            'tgl_kembali_plan' => 'required|date|after_or_equal:tgl_pinjam',
            'alat_id'          => 'required|array',
            'alat_id.*'        => 'exists:alat,id',
            'jumlah'           => 'required|array',
            'jumlah.*'         => 'integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::create([
                'user_id'          => $request->user_id,
                'tgl_pinjam'       => $request->tgl_pinjam,
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status'           => 'diajukan',
            ]);

            foreach ($request->alat_id as $index => $alatId) {
                $jumlahPinjam = $request->jumlah[$index];
                $alat = Alat::findOrFail($alatId);

                if ($alat->stok < $jumlahPinjam) {
                    throw new \Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi.");
                }

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id'       => $alatId,
                    'jumlah'        => $jumlahPinjam,
                ]);
            }

            // Catat Log Aktivitas
            LogAktivitas::create([
                'user_id'   => auth()->id(),
                'aktivitas' => 'Membuat transaksi peminjaman baru ID: #' . $peminjaman->id
            ]);

            DB::commit();
            return redirect()->route('admin.peminjaman.index')->with('success', 'Data peminjaman berhasil diajukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // 4. Memperbarui status peminjaman
    public function updateStatusPeminjaman(Request $request, $id)
    {
        // PERBAIKAN: detailPinjam diubah ke detailPinjams
        $peminjaman = Peminjaman::with('detailPinjams.alat')->findOrFail($id);

        $request->validate([
            'status' => 'required|in:diajukan,dipinjam,selesai,dikembalikan,telat',
        ]);

        DB::beginTransaction();
        try {
            $statusLama = strtolower($peminjaman->status);
            $statusBaru = strtolower($request->status);

            // Normalisasi alias 'selesai' menjadi 'dikembalikan'
            if ($statusBaru === 'selesai') {
                $statusBaru = 'dikembalikan';
            }

            // Jika tidak ada perubahan status, langsung kembali
            if ($statusLama === $statusBaru) {
                return redirect()->route('admin.peminjaman.index')->with('info', 'Status peminjaman tidak berubah.');
            }

            // 1. Kondisi saat barang MUALI DIPINJAM / TELAT (stok dikurangi)
            // Terjadi jika status lama belum mengurangi stok (misal: 'diajukan') dan diubah ke 'dipinjam' atau 'telat'
            if ($statusLama === 'diajukan' && in_array($statusBaru, ['dipinjam', 'telat'])) {
                foreach ($peminjaman->detailPinjams as $detail) {
                    $alat = $detail->alat;
                    if ($alat->stok < $detail->jumlah) {
                        throw new \Exception("Stok alat {$alat->nama_alat} tidak mencukupi untuk dipinjam.");
                    }
                    $alat->decrement('stok', $detail->jumlah);
                }
            }

            // 2. Kondisi saat barang DIKEMBALIKAN (stok ditambahkan kembali)
            // Terjadi jika status lama sedang aktif meminjam ('dipinjam' / 'telat') dan diubah ke 'dikembalikan'
            if (in_array($statusLama, ['dipinjam', 'telat']) && $statusBaru === 'dikembalikan') {
                foreach ($peminjaman->detailPinjams as $detail) {
                    $detail->alat->increment('stok', $detail->jumlah);
                }
            }

            // 3. Kondisi dibatalkan / dikembalikan ke 'diajukan' saat barang sedang dipinjam
            // Kembalikan stok yang sempat dikurangi sebelumnya
            if (in_array($statusLama, ['dipinjam', 'telat']) && $statusBaru === 'diajukan') {
                foreach ($peminjaman->detailPinjams as $detail) {
                    $detail->alat->increment('stok', $detail->jumlah);
                }
            }

            // Update status di database
            $peminjaman->update(['status' => $statusBaru]);

            // Catat Log Aktivitas
            LogAktivitas::create([
                'user_id'   => auth()->id(),
                'aktivitas' => 'Mengubah status peminjaman #' . $peminjaman->id . ' dari ' . $statusLama . ' menjadi ' . $statusBaru
            ]);

            DB::commit();
            return redirect()->route('admin.peminjaman.index')->with('success', 'Status peminjaman berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
    // 5. Menghapus data peminjaman
    public function destroyPeminjaman($id)
    {
        // PERBAIKAN: detailPinjam diubah ke detailPinjams
        $peminjaman = Peminjaman::with('detailPinjams')->findOrFail($id);
        $peminjamanId = $peminjaman->id;

        if (in_array($peminjaman->status, ['dipinjam', 'telat'])) {
            foreach ($peminjaman->detailPinjams as $detail) {
                $detail->alat->increment('stok', $detail->jumlah);
            }
        }

        $peminjaman->delete();

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id'   => auth()->id(),
            'aktivitas' => 'Menghapus data peminjaman #' . $peminjamanId
        ]);

        return redirect()->route('admin.peminjaman.index')->with('success', 'Data peminjaman berhasil dihapus.');
    }

    // 1. Menampilkan daftar pengembalian
    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');

        // PERBAIKAN: detailPinjam diubah ke detailPinjams
        $pengembalian = Pengembalian::with(['peminjaman.user', 'peminjaman.detailPinjams.alat', 'petugas'])
            ->when($search, function ($query, $search) {
                return $query->where('kondisi_kembali', 'like', "%{$search}%")
                    ->orWhereHas('peminjaman.user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengembalian.index', compact('pengembalian', 'search'));
    }

    // 2. Menampilkan form tambah pengembalian
    public function createPengembalian()
    {
        $peminjamans = Peminjaman::with('user')
            ->whereIn('status', ['dipinjam', 'telat'])
            ->whereDoesntHave('pengembalian')
            ->get();

        return view('admin.pengembalian.create', compact('peminjamans'));
    }

    // 3. Menyimpan data pengembalian baru dengan denda otomatis
    public function storePengembalian(Request $request)
    {
        $request->validate([
            'peminjaman_id'   => 'required|exists:peminjaman,id',
            'tgl_kembali'     => 'required|date',
            'kondisi_kembali' => 'required|string|max:255',
            'denda_tambahan'  => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // PERBAIKAN: detailPinjam diubah ke detailPinjams
            $peminjaman = Peminjaman::with('detailPinjams.alat')->findOrFail($request->peminjaman_id);

            // Hitung keterlambatan otomatis (dalam hari)
            $tglPlan = \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan);
            $tglAktual = \Carbon\Carbon::parse($request->tgl_kembali);

            $dendaOtomatis = 0;
            $tarifDendaPerHari = 5000;

            if ($tglAktual->greaterThan($tglPlan)) {
                $selisihHari = $tglPlan->diffInDays($tglAktual);
                $dendaOtomatis = $selisihHari * $tarifDendaPerHari;
            }

            $dendaTambahan = $request->denda_tambahan ?? 0;
            $totalDenda = $dendaOtomatis + $dendaTambahan;

            // Simpan data pengembalian
            Pengembalian::create([
                'peminjaman_id'   => $request->peminjaman_id,
                'tgl_kembali'     => $request->tgl_kembali,
                'kondisi_kembali' => $request->kondisi_kembali,
                'denda'           => $totalDenda,
                'petugas_id'      => auth()->id(),
            ]);

            // Ubah status peminjaman menjadi dikembalikan
            $peminjaman->update(['status' => 'dikembalikan']);

            // Kembalikan stok alat ke inventaris
            foreach ($peminjaman->detailPinjams as $detail) {
                $detail->alat->increment('stok', $detail->jumlah);
            }

            // Catat Log Aktivitas
            LogAktivitas::create([
                'user_id'   => auth()->id(),
                'aktivitas' => 'Memproses pengembalian peminjaman #' . $request->peminjaman_id
            ]);

            DB::commit();
            return redirect()->route('admin.pengembalian.index')
                ->with('success', 'Pengembalian berhasil diproses. Denda otomatis terhitung: Rp ' . number_format($totalDenda, 0, ',', '.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // 4. Menghapus data pengembalian
    public function destroyPengembalian($id)
    {
        $pengembalian = Pengembalian::with('peminjaman.detailPinjams.alat')->findOrFail($id);
        $peminjamanId = $pengembalian->peminjaman_id;

        DB::beginTransaction();
        try {
            $peminjaman = $pengembalian->peminjaman;

            if ($peminjaman) {
                $peminjaman->update(['status' => 'dipinjam']);
                foreach ($peminjaman->detailPinjams as $detail) {
                    $detail->alat->decrement('stok', $detail->jumlah);
                }
            }

            $pengembalian->delete();

            // Catat Log Aktivitas
            LogAktivitas::create([
                'user_id'   => auth()->id(),
                'aktivitas' => 'Menghapus data pengembalian untuk peminjaman #' . $peminjamanId
            ]);

            DB::commit();
            return redirect()->route('admin.pengembalian.index')->with('success', 'Data pengembalian berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}