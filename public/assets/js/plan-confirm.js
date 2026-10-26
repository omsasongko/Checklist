/**
 * LifeQuest — Activity plan confirmation dialog (Phase 2, CASE C).
 *
 * The server never replaces a schedule on its own. When a save finds that the
 * activity is already scheduled, it returns the submitted form plus a
 * structured payload, and this script:
 *
 *   1. re-opens the dialog so the user sees the existing and proposed
 *      schedules, and
 *   2. re-submits the same form with an explicit `operation` value.
 *
 * Cancelling does nothing: the server has already written nothing at that
 * point, so there is nothing to undo.
 */
(function () {
    'use strict';

    function each(list, fn) {
        Array.prototype.forEach.call(list, fn);
    }

    function setChoice(form, operation, planId) {
        var operationField = form.querySelector('input[name="operation"]');
        var targetField = form.querySelector('input[name="confirm_plan_id"]');

        if (operationField) {
            operationField.value = operation;
        }

        if (targetField) {
            targetField.value = planId === null || planId === undefined ? '' : String(planId);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('lq-plan-confirm-modal');
        var form = document.querySelector('.js-plan-form');

        if (!modalEl || !form || typeof bootstrap === 'undefined') {
            return;
        }

        var planForm = form;
        var existingPlanId = modalEl.getAttribute('data-existing-plan-id') || '';
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Default action is Cancel: pressing Escape or clicking the backdrop
        // simply closes the dialog, and the choice fields stay untouched.
        each(modalEl.querySelectorAll('[data-lq-plan-choice]'), function (button) {
            button.addEventListener('click', function () {
                var choice = button.getAttribute('data-lq-plan-choice');

                if (choice === 'replace') {
                    setChoice(planForm, 'REPLACE', existingPlanId);
                } else if (choice === 'add') {
                    setChoice(planForm, 'ADD_SESSION', '');
                }

                modal.hide();
                planForm.submit();
            });
        });

        if (modalEl.getAttribute('data-lq-autoshow') === '1') {
            modal.show();

            // Focus the Cancel button so the safe option is the default for
            // keyboard users, per the accessibility rules.
            var cancel = document.getElementById('lq-plan-confirm-cancel');

            if (cancel) {
                modalEl.addEventListener('shown.bs.modal', function () {
                    cancel.focus();
                });
            }
        }
    });
})();
