<?php

/** @var yii\web\View $this */
/** @var app\models\QaLog $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

$this->title = 'AI Chatbot Layanan Informasi Dana BOS';
?>
<div class="site-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <table class="table table-bordered">
        <tr>
            <td><b>Dasar</b></td>
            <td><?= Html::a('Permendikbudristek No. 63 Tahun 2023', 'https://github.com/esnanta/ai-chatbot-dana-bos-api/blob/main/knowledge_base/Permendikbudriset_No_63_Tahun_2023.pdf', ['target' => '_blank']) ?></td>
        </tr>
        <tr>
            <td><b>Bantuan</b></td>
            <td>
               Contoh <?= Html::a('pertanyaan', ['suggestion/index']) ?>.
            </td>
        </tr>
        <tr>
            <td><b>About</b></td>
            <td>Penjelasan mengenai chatbot ini dapat ditemukan di menu <?= Html::a('About', ['site/about']) ?>.</td>
        </tr>
        <tr>
            <td><b>Log</b></td>
            <td>Riwayat pertanyaan dan jawaban dapat dilihat di menu <?= Html::a('Log', ['site/log']) ?>.</td>
        </tr>
    </table>


    <?php $form = ActiveForm::begin([
        'id' => 'chatbot-form',
        'action' => Url::to(['site/index']),
        'options' => ['class' => 'form-horizontal'],
        'enableAjaxValidation' => false, // AJAX ditangani secara manual
    ]); ?>

    <!-- ✅ DITAMBAHKAN: Input untuk pencarian dengan fitur autocomplete -->
    <div style="position: relative;">
        <?= $form->field($model, 'question')->textInput([
            'id' => 'question-input',
            'autofocus' => true,
            'placeholder' => 'Ketik pertanyaan...',
            'autocomplete' => 'off'
        ]) ?>
        <div id="suggestions"></div>
    </div>
    <br>
    <div class="form-group">
        <?= Html::submitButton('Send', ['class' => 'btn btn-primary']) ?>
        <!-- ✅ DITAMBAHKAN: Loading Indicator -->
        <div id="loading-indicator" style="display: none;">
            <div class="spinner"></div> Loading...
        </div>
    </div>

    <?php ActiveForm::end(); ?>

    <div id="answer" style="margin-top: 20px;"></div>

    <input type="hidden" id="answer-id">

    <!-- Tombol Upvote & Downvote (Disembunyikan awalnya) -->
    <div id="vote-buttons" style="display: none; margin-top: 10px;">
        <button id="upvote-btn" class="btn btn-success">👍 Upvote</button>
        <button id="downvote-btn" class="btn btn-danger">👎 Downvote</button>
    </div>

</div>

<?php
$this->registerJs(<<<JS
$(document).ready(function() {

    // ✅ DITAMBAHKAN: Fitur Autocomplete untuk pertanyaan
    $("#question-input").on("keyup", function() {
        let query = $(this).val();
        
        if (query.length < 3) {
            $("#suggestions").html("").hide();
            return;
        }

        $.get("site/suggestion", { query: query }, function(data) {
            let suggestionBox = $("#suggestions");
            suggestionBox.html("").show();

            data.forEach(function(item) {
                let div = $("<div>").text(item.question).addClass("suggestion-item");
                div.on("click", function() {
                    $("#question-input").val(item.question);
                    suggestionBox.hide();
                });
                suggestionBox.append(div);
            });
        }, "json");
    });

    // ✅ DITAMBAHKAN: Menutup rekomendasi saat klik di luar
    $(document).on("click", function(event) {
        if (!$(event.target).closest("#question-input, #suggestions").length) {
            $("#suggestions").hide();
        }
    });

    // Handle Form Submit
    $('#chatbot-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();

        // ✅ DITAMBAHKAN: Tampilkan loading indicator
        $('#loading-indicator').show();
        $('#answer').html(''); // Kosongkan jawaban sebelumnya

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                // Sembunyikan loading indicator
                $('#loading-indicator').hide();

                if (response && response.answer) {
                    // Tampilkan jawaban
                    $('#answer').html('<b>Answer:</b> ' + response.answer);

                    // Simpan ID jawaban untuk upvote/downvote
                    $('#answer-id').val(response.id);

                    // Tampilkan tombol upvote/downvote
                    $('#vote-buttons').show();
                } else {
                    $('#answer').html('<b>Error:</b> No answer received or invalid response.');
                }
            },
            error: function(xhr, status, error) {
                // Sembunyikan loading indicator pada error
                $('#loading-indicator').hide();
                $('#answer').html('<b>Error:</b> ' + error);
            }
        });
    });

    // ✅ DITAMBAHKAN: Handle Upvote
    var csrfToken = $('meta[name="csrf-token"]').attr("content");

    $('#upvote-btn').on('click', function() {
        var id = $('#answer-id').val();
        $.post({
            url: 'site/upvote',
            data: {id: id, _csrf: csrfToken},
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Upvote berhasil! Total Upvotes: ' + response.upvote);
                } else {
                    alert('Upvote gagal!');
                }
            }
        });
    });

    // ✅ DITAMBAHKAN: Handle Downvote
    $('#downvote-btn').on('click', function() {
        var id = $('#answer-id').val();
        $.post({
            url: 'site/downvote',
            data: {id: id, _csrf: csrfToken},
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Downvote berhasil! Total Downvotes: ' + response.downvote);
                } else {
                    alert('Downvote gagal!');
                }
            }
        });
    });

});
JS
);
?>

<style>
    /* ✅ DITAMBAHKAN: Styling untuk kotak saran */
    #suggestions {
        border: 1px solid #ddd;
        max-width: 400px;
        background: #fff;
        position: absolute;
        z-index: 1000;
        display: none;
        border-radius: 5px;
        overflow: hidden;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    }
    .suggestion-item {
        padding: 8px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }
    .suggestion-item:hover {
        background: #f2f2f2;
    }

    /* ✅ DITAMBAHKAN: Styling untuk loading indicator */
    #loading-indicator {
        display: inline-block; /* Agar sejajar dengan tombol */
        margin-left: 10px;
        color: #555;
    }

    .spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        border-left-color: #4CAF50; /* Warna hijau */
        border-radius: 50%;
        width: 20px;
        height: 20px;
        animation: spin 1s linear infinite;
        display: inline-block;
        vertical-align: middle;
        margin-right: 5px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>