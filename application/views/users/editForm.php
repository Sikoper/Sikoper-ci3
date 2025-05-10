<section class="section">
    <div class="card">
        <div class="card-header">
            <a href="<?= site_url('users') ?>" class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>
                    <input type="hidden" id="id" name="id" value="<?= $users->id ?>">
                    <div class="form-group" style="height: 80px;">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?= $users->nama ?>" placeholder="Gunakan nama panggilan">
                        <div id="errorNama" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?= $users->username ?>" placeholder="Gunakan kombinasi huruf dan angka">
                        <div id="errorUserName" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Kosongkan bila tidak ingin mengganti password">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword" style="border-radius: 0 5px 5px 0;">
                                    <i class="fa fa-eye-slash fa-fw"></i>
                                </button>
                            </div>
                        </div>
                        <div id="errorPassword" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= base_url('users') ?>'" class="btn btn-danger">Batal</button>
                    </div>

                    <?= form_close() ?>
                </div>
                <div class="col-md-3"></div>
            </div>
        </div>
    </div>
</section>
<script>
    $(document).ready(function() {
        $('#togglePassword').on('click', function() {
            var passwordField = $('#password');
            var passwordFieldType = passwordField.attr('type');

            if (passwordFieldType === 'password') {
                passwordField.attr('type', 'text');
                $(this).html('<i class="fa fa-eye fa-fw"></i>');
            } else {
                passwordField.attr('type', 'password');
                $(this).html('<i class="fa fa-eye-slash fa-fw"></i>');
            }
        });

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('users/updateData') ?>",
                data: data,
                dataType: "json",
                processData: false,
                contentType: false,
                cache: false,
                beforeSend: function() {
                    $('#tombol_simpan').prop('disabled', true)
                    $('#tombol_simpan').html('<i class="fa fa-spin fa-spinner"></i>')
                },
                complete: function() {
                    $('#tombol_simpan').prop('disabled', false)
                    $('#tombol_simpan').html('Save')
                },
                success: function(response) {
                    if (response.error) {
                        let dataError = response.error;
                        if (dataError.errorNama) {
                            $('#errorNama').html(dataError.errorNama).show();
                            $('#nama').addClass('is-invalid');
                        } else {
                            $('#errorNama').fadeOut();
                            $('#nama').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorUserName) {
                            $('#errorUserName').html(dataError.errorUserName).show();
                            $('#username').addClass('is-invalid');
                        } else {
                            $('#errorUserName').fadeOut();
                            $('#username').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorPassword) {
                            $('#errorPassword').html(dataError.errorPassword).show();
                            $('#password').addClass('is-invalid');
                        } else {
                            $('#errorPassword').fadeOut();
                            $('#password').removeClass('is-invalid').addClass('is-valid');
                        }
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
                            html: response.success
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = '<?= base_url('users') ?>';
                            }
                        });
                    }
                },
                error: function(xhr, thrownError) {
                    alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                }
            });
        });
    });

</script>