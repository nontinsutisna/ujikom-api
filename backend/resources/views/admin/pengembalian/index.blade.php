@extends('layouts.app')

@section('title', 'Riwayat Pengembalian - Panel Admin')
@section('header-title', 'Riwayat Pengembalian Alat')

@section('content')
    <!-- Notifikasi Error (jika ada kegagalan) -->
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <!-- Header & Pencarian -->
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Daftar Pengembalian Selesai</h3>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <!-- Form Search -->
                <form action="{{ route('admin.pengembalian.index') }}" method="GET" class="flex w-full md:w-80">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari peminjam..."
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.pengembalian.index') }}"
                            class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition">
                            Reset
                        </a>
                    @endif
                </form>

                <!-- Tombol Tambah Pengembalian -->
                <a href="{{ route('admin.pengembalian.create') }}" 
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap">
                    + Proses Pengembalian
                </a>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Peminjam</th>
                        <th class="py-3 px-4 border-b">Alat</th>
                        <th class="py-3 px-4 border-b">Tgl Kembalikan</th>
                        <th class="py-3 px-4 border-b">Kondisi</th>
                        <th class="py-3 px-4 border-b">Denda</th>
                        <th class="py-3 px-4 border-b">Petugas</th>
                        <th class="py-3 px-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse($pengembalian as $item)
                        <tr class="hover:bg-gray-50 transition align-top">
                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ optional(optional($item->peminjaman)->user)->name ?? 'User / Data Dihapus' }}
                            </td>
                            <td class="py-3 px-4 border-b">
                                <ul class="list-disc list-inside space-y-1">
                                    {{-- Penanganan Aman jika detailPinjam / detailPinjams null --}}
                                    @if(optional($item->peminjaman)->detailPinjam)
                                        @foreach($item->peminjaman->detailPinjam as $detail)
                                            <li>{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }} ({{ $detail->jumlah }} pcs)</li>
                                        @endforeach
                                    @elseif(optional($item->peminjaman)->detailPinjams)
                                        @foreach($item->peminjaman->detailPinjams as $detail)
                                            <li>{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }} ({{ $detail->jumlah }} pcs)</li>
                                        @endforeach
                                    @else
                                        <li class="text-gray-400 italic">Data alat tidak ditemukan</li>
                                    @endif
                                </ul>
                            </td>
                            <td class="py-3 px-4 border-b text-xs">
                                {{ $item->tgl_dikembalikan ?? $item->tgl_kembali ?? '-' }}
                            </td>
                            <td class="py-3 px-4 border-b">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                    @if(strtolower($item->kondisi_alat ?? $item->kondisi_kembali) == 'baik') bg-emerald-100 text-emerald-800
                                    @elseif(strtolower($item->kondisi_alat ?? $item->kondisi_kembali) == 'rusak') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800 @endif">
                                    {{ ucfirst($item->kondisi_alat ?? $item->kondisi_kembali ?? 'Selesai') }}
                                </span>
                            </td>

                            <!-- TEKS DENDA BERWARNA MERAH -->
                            <td class="py-3 px-4 border-b font-bold text-red-600">
                                @if(($item->denda ?? 0) > 0)
                                    Rp {{ number_format($item->denda, 0, ',', '.') }}
                                @else
                                    <span class="text-gray-500 font-semibold">Rp 0</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 border-b text-xs text-gray-600">
                                {{ $item->petugas->name ?? 'Sistem' }}
                            </td>
                            <td class="py-3 px-4 border-b text-center">
                                <!-- Form & Tombol Hapus dengan SweetAlert -->
                                <form id="delete-form-{{ $item->id }}" action="{{ route('admin.pengembalian.destroy', $item->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete({{ $item->id }})" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-500">Belum ada riwayat pengembalian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Box -->
        @if(is_object($pengembalian) && method_exists($pengembalian, 'hasPages') && $pengembalian->hasPages())
            <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-end">
                {{ $pengembalian->links() }}
            </div>
        @endif
    </div>

    <!-- Script SweetAlert Konfirmasi Hapus -->
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data riwayat pengembalian ini akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
@endsection