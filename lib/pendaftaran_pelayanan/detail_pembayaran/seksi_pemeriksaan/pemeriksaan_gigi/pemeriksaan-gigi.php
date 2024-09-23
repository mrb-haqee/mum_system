<?php

$FORM_TYPE = base64_encode('pemeriksaan-gigi');

$dataOralUpdate = statementWrapper(
    DML_SELECT,
    'SELECT * FROM pasien_pemeriksaan_dental_oral WHERE kodeAntrian = ?',
    [$kodeAntrian]
);

$dataSupportingUpdate = statementWrapper(
    DML_SELECT,
    'SELECT * FROM pasien_pemeriksaan_dental_supporting WHERE kodeAntrian = ?',
    [$kodeAntrian]
);

$dataVitalityUpdate = statementWrapper(
    DML_SELECT,
    'SELECT * FROM pasien_pemeriksaan_dental_vitality WHERE kodeAntrian = ?',
    [$kodeAntrian]
);

?>
<div class="card card-custom">
    <!-- CARD HEADER -->
    <div class="card-header card-header-tabs-line">
        <!-- CARD TITLE -->
        <div class="card-title">
            <h3 class="card-label">
                <span class="card-label font-weight-bolder text-dark d-block">
                    <i class="fas fa-diagnoses text-dark"></i> Pemeriksaan Pasien Gigi
                </span>
                <span class="mt-3 font-weight-bold font-size-sm">
                    <?= PAGE_TITLE; ?>
                </span>
            </h3>
        </div>
        <!-- END CARD TITLE -->
        <div class="card-toolbar">
            <ul class="nav nav-tabs nav-bold nav-tabs-line">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#boxFormOral">
                        <span class="nav-icon"><i class="flaticon2-chat-1"></i></span>
                        <span class="nav-text">Oral</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#boxFormSupporting">
                        <span class="nav-icon"><i class="flaticon2-drop"></i></span>
                        <span class="nav-text">Supporting</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#boxFormVitality">
                        <span class="nav-icon"><i class="flaticon2-drop"></i></span>
                        <span class="nav-text">Vitality</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!-- END CARD HEADER -->

    <!-- CARD BODY -->
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="boxFormOral" role="tabpanel" aria-labelledby="boxFormOral">

                <input type="hidden" name="__FORM_TYPE__" value="<?= $FORM_TYPE ?>">

                <div class="form-row">
                    <div class="form-group col-sm-4">
                        <label><i class="fas fa-info-circle"></i> Debris</label>
                        <select id="debrisOption" disabled class="form-control selectpicker" data-live-search="true">
                            <?php
                            $arrayNoYes = array('No', 'Yes');
                            foreach ($arrayNoYes as $option) {
                                $selected = selected($option, $dataOralUpdate['debrisOption']);
                            ?>
                                <option value="<?= $option ?>" <?= $selected ?>>
                                    <?= $option ?>
                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-8">
                        <label><i class="fas fa-book"></i> Note</label>
                        <input type="text" readonly class="form-control" placeholder="Note" value="<?= $dataOralUpdate['debrisNote'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-4">
                        <label><i class="fas fa-info-circle"></i> Plaque</label>
                        <select disabled class="form-control selectpicker" data-live-search="true">
                            <?php
                            foreach ($arrayNoYes as $option) {
                                $selected = selected($option, $dataOralUpdate['plaqueOption']);
                            ?>
                                <option value="<?= $option ?>" <?= $selected ?>>
                                    <?= $option ?>
                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-8">
                        <label><i class="fas fa-book"></i> Note</label>
                        <input type="text" readonly class="form-control" placeholder="Note" value="<?= $dataOralUpdate['plaqueNote'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-4">
                        <label><i class="fas fa-info-circle"></i> Calculus</label>
                        <select disabled class="form-control selectpicker" data-live-search="true">
                            <?php
                            foreach ($arrayNoYes as $option) {
                                $selected = selected($option, $dataOralUpdate['calculusOption']);
                            ?>
                                <option value="<?= $option ?>" <?= $selected ?>>
                                    <?= $option ?>
                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-8">
                        <label><i class="fas fa-book"></i> Note</label>
                        <input type="text" readonly class="form-control" placeholder="Note" value="<?= $dataOralUpdate['calculusNote'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-4">
                        <label><i class="fas fa-info-circle"></i> Gingiva</label>
                        <select disabled class="form-control selectpicker" data-live-search="true">
                            <?php
                            $arrayGingiva = array('Unremarkable', 'Appearance of Diasease/Disorder');
                            foreach ($arrayGingiva as $option) {
                                $selected = selected($option, $dataOralUpdate['gingivaOption']);
                            ?>
                                <option value="<?= $option ?>" <?= $selected ?>>
                                    <?= $option ?>
                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-8">
                        <label><i class="fas fa-book"></i> Note</label>
                        <input type="text" readonly class="form-control" placeholder="Note" value="<?= $dataOralUpdate['gingivaNote'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-4">
                        <label><i class="fas fa-info-circle"></i> Mucosae</label>
                        <select disabled class="form-control selectpicker" data-live-search="true">
                            <?php
                            foreach ($arrayGingiva as $option) {
                                $selected = selected($option, $dataOralUpdate['mucosaeOption']);
                            ?>
                                <option value="<?= $option ?>" <?= $selected ?>>
                                    <?= $option ?>
                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-8">
                        <label><i class="fas fa-book"></i> Note</label>
                        <input type="text" readonly class="form-control" placeholder="Note" value="<?= $dataOralUpdate['mucosaeNote'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-4">
                        <label><i class="fas fa-info-circle"></i> Jaw Relation</label>
                        <select disabled class="form-control selectpicker" data-live-search="true">
                            <?php
                            $arrayJawRelation = array('Orthognathic', 'Retrognathic', 'Prognathic');
                            foreach ($arrayJawRelation as $option) {
                                $selected = selected($option, $dataOralUpdate['jawRelationOption']);
                            ?>
                                <option value="<?= $option ?>" <?= $selected ?>>
                                    <?= $option ?>
                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-8">
                        <label><i class="fas fa-book"></i> Note</label>
                        <input type="text" readonly class="form-control" placeholder="Note" value="<?= $dataOralUpdate['jawRelationNote'] ?? '' ?>">
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="boxFormSupporting" role="tabpanel" aria-labelledby="boxFormSupporting">

                <input type="hidden" name="__FORM_TYPE__" value="<?= $FORM_TYPE ?>">

                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Percussion</label>
                    <input type="text" id="percussion" readonly class="form-control" placeholder="Percussion" value="<?= $dataSupportingUpdate['percussion'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Pressure</label>
                    <input type="text" readonly class="form-control" placeholder="Pressure" value="<?= $dataSupportingUpdate['pressure'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Palpation</label>
                    <input type="text" readonly class="form-control" placeholder="Palpation" value="<?= $dataSupportingUpdate['palpation'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Mobility</label>
                    <input type="text" readonly class="form-control" placeholder="Mobility" value="<?= $dataSupportingUpdate['mobility'] ?? '' ?>">
                </div>
            </div>
            <div class="tab-pane fade" id="boxFormVitality" role="tabpanel" aria-labelledby="boxFormVitality">

                <input type="hidden" name="__FORM_TYPE__" value="<?= $FORM_TYPE ?>">
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Tooth</label>
                    <input type="text" id="tooth" readonly class="form-control" placeholder="Tooth" value="<?= $dataVitalityUpdate['tooth'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Probe Test (Sondation)</label>
                    <input type="text" readonly class="form-control" placeholder="Probe Test (Sondation)" value="<?= $dataVitalityUpdate['probeTest'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Heat</label>
                    <input type="text" readonly class="form-control" placeholder="heat" value="<?= $dataVitalityUpdate['heat'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Cold</label>
                    <input type="text" readonly class="form-control" placeholder="Cold" value="<?= $dataVitalityUpdate['cold'] ?? '' ?>">
                </div>

            </div>
        </div>
    </div>
</div>
<?php

?>