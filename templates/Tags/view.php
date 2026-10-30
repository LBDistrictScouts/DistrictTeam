<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Tag $tag
 * @var list<\App\Model\Entity\Appointment> $currentAppointments
 * @var bool $groupEmailsOnly
 */
$this->assign('title', $tag->tag_label);
?>
<div class="workspace-page">
    <nav class="workspace-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Tags'), ['action' => 'index']) ?> / <?= h($tag->tag_label) ?>
    </nav>
    <?= $this->element('Workspace/index_header', [
        'title' => $tag->tag_label,
        'description' => __('Tag code: {0}', $tag->tag_code),
        'actions' => [
            ['label' => __('Edit tag'), 'url' => ['action' => 'edit', $tag->id]],
            [
                'label' => __('Delete tag'),
                'url' => ['action' => 'delete', $tag->id],
                'method' => 'post',
                'secondary' => true,
                'confirm' => __('Delete this tag and remove its assignments?'),
            ],
        ],
    ]) ?>
    <p class="tag-cognito-status">
        <strong><?= __('Cognito permissions group') ?>:</strong>
        <?= $tag->is_cognito_permissions_group ? __('Yes') : __('No') ?>
    </p>
    <div class="tag-usage-grid">
        <section class="workspace-form-panel">
            <h2><?= __('Teams') ?></h2>
            <?php if ($tag->teams) : ?>
                <ul>
                    <?php foreach ($tag->teams as $team) : ?>
                        <?php $teamUrl = ['controller' => 'Teams', 'action' => 'view', $team->id]; ?>
                        <li><?= $this->Html->link($team->team_name, $teamUrl) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?= __('No teams use this tag.') ?></p>
            <?php endif; ?>
        </section>
        <section class="workspace-form-panel">
            <h2><?= __('Roles') ?></h2>
            <?php if ($tag->roles) : ?>
                <ul>
                    <?php foreach ($tag->roles as $role) : ?>
                        <?php
                        $roleUrl = ['controller' => 'Roles', 'action' => 'view', $role->id];
                        $roleLink = $this->Html->link($role->name, $roleUrl);
                        $teamLink = $role->team
                            ? $this->Html->link($role->team->team_name, [
                                'controller' => 'Teams',
                                'action' => 'view',
                                $role->team->id,
                            ])
                            : null;
                        ?>
                        <li>
                            <?= $roleLink ?>
                            <?php if ($teamLink) : ?>
                                · <?= $teamLink ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?= __('No roles use this tag.') ?></p>
            <?php endif; ?>
        </section>
        <section class="workspace-form-panel">
            <h2><?= __('Appointments') ?></h2>
            <?php if ($tag->appointments) : ?>
                <ul>
                    <?php foreach ($tag->appointments as $appointment) : ?>
                        <?php
                        $appointmentText = $appointment->member->full_name . ' · ' . $appointment->role->name;
                        $appointmentUrl = ['controller' => 'Appointments', 'action' => 'view', $appointment->id];
                        ?>
                        <li><?= $this->Html->link($appointmentText, $appointmentUrl) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?= __('No appointments use this tag.') ?></p>
            <?php endif; ?>
        </section>
    </div>
    <section class="workspace-table-panel tag-current-members" aria-labelledby="tag-current-members-heading">
        <div class="workspace-table-heading">
            <h2 id="tag-current-members-heading"><?= __('Members currently matched by this tag') ?></h2>
            <span><?= $this->Number->format(count($currentAppointments)) ?></span>
        </div>
        <?= $this->Form->create(null, [
            'type' => 'get',
            'url' => ['action' => 'view', $tag->id],
            'class' => 'tag-member-filter',
        ]) ?>
        <input type="hidden" name="group_emails_only" value="0">
        <label for="tag-group-emails-only">
            <input
                id="tag-group-emails-only"
                type="checkbox"
                name="group_emails_only"
                value="1"
                <?= $groupEmailsOnly ? 'checked' : '' ?>
            >
            <?= __('Show only appointments with group email addresses') ?>
        </label>
        <button type="submit" class="button button-outline"><?= __('Apply') ?></button>
        <?= $this->Form->end() ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?= __('Member') ?></th>
                        <th><?= __('Appointment') ?></th>
                        <th><?= __('Contact method') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$currentAppointments) : ?>
                        <tr>
                            <td colspan="3" class="workspace-empty">
                                <?= __('No members currently match this tag.') ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($currentAppointments as $appointment) : ?>
                        <?php
                        $memberUrl = ['controller' => 'Members', 'action' => 'view', $appointment->member->id];
                        $memberLink = $this->Html->link($appointment->member->full_name, $memberUrl);
                        $roleUrl = ['controller' => 'Appointments', 'action' => 'view', $appointment->id];
                        $roleLink = $this->Html->link($appointment->role->name, $roleUrl);
                        $startDate = $this->Time->format($appointment->effective_start_date, 'd MMM yyyy');
                        $endDate = $appointment->effective_end_date
                            ? __(' to {0}', $this->Time->format($appointment->effective_end_date, 'd MMM yyyy'))
                            : '';
                        $contactMethodType = $appointment->member_contact_method?->contact_method_type->label();
                        ?>
                        <tr>
                            <td><?= $memberLink ?></td>
                            <td>
                                <?= $roleLink ?>
                                <small>
                                    <?= __('From {0}', $startDate) ?><?= $endDate ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($appointment->member_contact_method) : ?>
                                    <span><?= h($contactMethodType) ?></span>
                                    <?= h($appointment->member_contact_method->contact_method) ?>
                                <?php else : ?>
                                    <?= __('No contact method assigned') ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
