@extends('layouts.app')

@section('title', 'Riwayat Peminjaman - Panel Peminjam')
@section('header-title', 'Riwayat Peminjaman Alat Anda')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Daftar Pengajuan & Peminjaman Saya</h3>
            <a href="{{ route('peminjam.katalog') }}" 
                class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                + Pinjam Alat Lagi
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b width-10">No</th>
                        <th class="py-3 px-4 border-b">Alat yang Dipinjam</th>
                        <th class="py-3 px-4 border-b">Tgl Pinjam / Rencana Kembali</th>
                        <th class="py-3 px-4 border-b">Status</th>
                        <th class="py-3 px-4 border-b">Info Denda</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse($peminjamans as $index => $item)
                        <tr class="hover:bg-gray-50 transition align-top">
                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $peminjamans->firstItem() + $index }}
                            </td>
                            <td class="py-3 px-4 border-b">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($item->detailPinjams as $detail)
                                        <li>
                                            <span class="font-semibold">{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}</span>
                                            <span class="text-xs bg-gray-200 px-1.5 py-0.5 rounded text-gray-700">({{ $detail->jumlah }} pcs)</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="py-3 px-4 border-b text-xs text-gray-600">
                                <span class="block">Tgl Pinjam: {{ $item->tgl_pinjam ?? $item->tanggal_pinjam }}</span>
                                <span class="block font-semibold">Rencana Kembali: {{ $item->tgl_kembali_plan ?? $item->tanggal_kembali }}</span>
                            </td>
                            <td class="py-3 px-4 border-b">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                    @if(strtolower($item->status) == 'diajukan') bg-yellow-100 text-yellow-800
                                    @elseif(strtolower($item->status) == 'dipinjam') bg-blue-100 text-blue-800
                                    @elseif(in_array(strtolower($item->status), ['dikembalikan', 'selesai'])) bg-emerald-100 text-emerald-800
                                    @else bg-red-100 text-red-800 @endif">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 border-b text-xs">
                                @if($item->pengembalian && $item->pengembalian->denda > 0)
                                    <span class="text-red-600 font-bold">Rp {{ number_format($item->pengembalian->denda, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-gray-400 italic">Tidak ada denda</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-500">
                                Anda belum pernah mengajukan peminjaman alat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($peminjamans->hasPages())
            <div class="p-4 border-t border-gray-200 bg-gray-50">
                {{ $peminjamans->links() }}
            </div>
        @endif
    </div>
@endsection