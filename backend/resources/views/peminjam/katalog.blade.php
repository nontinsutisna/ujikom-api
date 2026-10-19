@extends('layouts.app')

@section('title', 'Katalog Alat - Panel Peminjam')
@section('header-title', 'Daftar Alat Tersedia')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="space-y-6">
        <!-- Form Search Alat -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
            <h3 class="font-bold text-gray-800 text-lg">Pilih Alat yang Ingin Dipinjam</h3>
            <form action="{{ route('peminjam.katalog') }}" method="GET" class="flex w-full md:w-80">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama alat..."
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition">
                    Cari
                </button>
            </form>
        </div>

        <!-- Daftar Katalog Alat (Grid Kartu) -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($alats as $item)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between">
                    <div class="p-4">
                        <img src="{{ asset($item->gambar ?? 'https://via.placeholder.com/150') }}" class="w-full h-40 object-cover rounded mb-3">
                        <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-1 rounded-full">
                            {{ $item->kategori->nama_kategori ?? 'Umum' }}
                        </span>
                        <h4 class="font-bold text-gray-800 mt-2 text-base">{{ $item->nama_alat }}</h4>
                        <p class="text-xs text-gray-500 mt-1">Stok Tersedia: <b class="text-emerald-600">{{ $item->stok }} pcs</b></p>
                    </div>

                    <!-- Form Pengajuan Peminjaman -->
                    <div class="p-4 border-t border-gray-100 bg-gray-50">
                        <form action="{{ route('peminjam.peminjaman.ajukan') }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="hidden" name="alat_id[]" value="{{ $item->id }}">
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Jumlah Pinjam</label>
                                <input type="number" name="jumlah[]" min="1" max="{{ $item->stok }}" value="1" required 
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Rencana Tgl Kembali</label>
                                <input type="date" name="tgl_kembali_plan" required 
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none">
                            </div>

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded text-xs transition">
                                Ajukan Peminjaman
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white p-8 rounded-lg text-center text-gray-500 border border-gray-200">
                    Tidak ada alat yang tersedia untuk dipinjam saat ini.
                </div>
            @endforelse
        </div>

        @if($alats->hasPages())
            <div class="mt-4">
                {{ $alats->links() }}
            </div>
        @endif
    </div>
@endsection