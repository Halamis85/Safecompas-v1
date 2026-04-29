{{-- resources/views/lekarnicky/modals/plan-budovy.blade.php --}}

<div class="modal fade" id="planBudovyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content glass-modal border-0">
            <div class="modal-header border-0 p-3">
                <h5 class="modal-title fw-bold fs-3">
                    <i class="fa-solid fa-map-location-dot me-2 text-primary"></i>
                    Plán budovy
                </h5>

                {{-- Toggle pro režim editace - pouze pro lekarnicke.edit --}}
                @php
                    $perms = session('user.permissions', []);
                    $canEditPlan = session('user.is_super_admin')
                        || in_array('lekarnicke.edit', $perms);
                @endphp

                @if($canEditPlan)
                    <div class="form-check form-switch ms-auto me-3">
                        <input class="form-check-input" type="checkbox" id="planEditToggle">
                        <label class="form-check-label" for="planEditToggle">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Režim úprav (drag &amp; drop)
                        </label>
                    </div>
                @endif

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
            </div>

            <div class="modal-body p-3">
                <div id="plan-help-text" class="alert alert-info d-none mb-3">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Režim úprav je aktivní. Přetáhněte puntík na novou pozici.
                    Lékárničky bez pozice najdete v seznamu pod plánem - přetáhněte je na plán.
                </div>

                {{-- Wrapper pro plán + puntíky. Ten dvouúrovňový wrapper umožňuje
                     responsive škálování bez ztráty pozic puntíků (procenta). --}}
                <div id="plan-container" class="plan-container">
                    <img id="plan-image"
                         src="{{ asset('images/plan-budovy.png') }}"
                         alt="Plán budovy"
                         class="plan-image"
                         draggable="false">

                    {{-- Puntíky se renderují dynamicky JS přes #plan-markers --}}
                    <div id="plan-markers" class="plan-markers"></div>
                </div>

                {{-- Seznam lékárniček bez pozice (zobrazí se pouze v režimu úprav) --}}
                <div id="plan-unassigned-wrapper" class="mt-4 d-none">
                    <h6 class="fw-bold mb-2">
                        <i class="fa-solid fa-circle-question me-1"></i>
                        Lékárničky bez pozice na plánu
                    </h6>
                    <div id="plan-unassigned-list" class="d-flex flex-wrap gap-2">
                        {{-- Dynamicky plněno --}}
                    </div>
                    <small class="text-muted">
                        Přetáhněte tyto puntíky na plán pro nastavení pozice.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
