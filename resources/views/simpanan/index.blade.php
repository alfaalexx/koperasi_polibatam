@extends('layouts.main')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Daftar Simpanan Anggota Koperasi</h1>
        </div>
        <div class="container-fluid">

            <div class="row justify-content-center">
                <div class="col-sm-12">
                    <div class="d-flex justify-content-start mb-3">
                        <a href="{{ route('simpanan.create') }}" class="btn btn-primary">Tambah Simpanan</a>
                    </div>
                    <div class="card">

                        @if (session()->has('success'))
                            <div class="alert alert-success alert-dismissible fade show m-4" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif


                        <div class="card-body table-responsive">
                            <form action="{{ route('simpanan.index') }}" method="GET">
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <select name="jenis_simpanan" class="form-control">
                                            <option value="">Pilih Jenis Simpanan</option>
                                            <option value="pokok"
                                                {{ request('jenis_simpanan') == 'pokok' ? 'selected' : '' }}>Simpanan Pokok
                                            </option>
                                            <option value="wajib"
                                                {{ request('jenis_simpanan') == 'wajib' ? 'selected' : '' }}>Simpanan Wajib
                                            </option>
                                            <option value="sukarela"
                                                {{ request('jenis_simpanan') == 'sukarela' ? 'selected' : '' }}>Simpanan
                                                Sukarela</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="search"
                                                placeholder="Cari nama anggota atau nomor anggota"
                                                value="{{ request('search') }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">
                                                    <a
                                                        onclick="document.getElementsByName('search')[0].value = ''; window.location.href='{{ route('simpanan.index') }}'">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </span>
                                                <button type="submit" class="btn btn-primary"><i
                                                        class="fa fa-search"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nomor Anggota</th>
                                        <th>Nama Anggota</th>
                                        <th>Jenis Simpanan</th>
                                        <th>Jumlah</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($simpanan as $item)
                                        <tr>
                                            <td>{{ $item->id }}</td>
                                            <td>{{ $item->user->no_anggota }}</td>
                                            <td>{{ $item->user->name }}</td>
                                            <td>{{ ucfirst($item->jenis_simpanan) }}</td>
                                            <td> @currency($item->jumlah)</td>
                                            <td>{{ $item->tanggal }}</td>
                                            <td>{{ $item->keterangan }}</td>
                                            <td>
                                                <a href="{{ route('simpanan.edit', $item->id) }}"
                                                    class="btn btn-sm btn-warning">Edit</a>
                                                <form action="{{ route('simpanan.destroy', $item->id) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-lg-end mt-4">
                                {{ $simpanan->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
