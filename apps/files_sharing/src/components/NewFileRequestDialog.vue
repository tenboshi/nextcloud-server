<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog can-close
		class="file-request-dialog"
		data-cy-file-request-dialog
		:close-on-click-outside="false"
		:name="t('files_sharing', 'Create a file request')"
		size="normal"
		@closing="onCancel">
		<!-- Header -->
		<NcNoteCard type="info" class="file-request-dialog__header">
			<p id="file-request-dialog-description" class="file-request-dialog__description">
				{{ t('files_sharing', 'You can use file requests to collect files from others, even if they don\'t have an account.') }}
				{{ t('files_sharing', 'The files will be saved in a folder of your choice.') }}<br>
				{{ t('files_sharing', 'To ensure you can receive the necessary files, please verify your available storage capacity') }}
			</p>
		</NcNoteCard>

		<!-- Main form -->
		<form ref="form"
			class="file-request-dialog__form"
			aria-labelledby="file-request-dialog-description"
			data-cy-file-request-dialog-form
			@submit.prevent.stop="onSubmit">
			<!-- Request label -->
			<fieldset class="file-request-dialog__label" data-cy-file-request-dialog-fieldset="label">
				<legend>
					{{ t('files_sharing', 'What are you requesting ?') }}
				</legend>
				<NcTextField :value.sync="label"
					:label-outside="true"
					:placeholder="t('files_sharing', 'Birthday party photos, History assignment…')"
					:required="false" />
			</fieldset>

			<!-- Request destination -->
			<fieldset class="file-request-dialog__destination" data-cy-file-request-dialog-fieldset="destination">
				<legend>
					{{ t('files_sharing', 'Where should these files go ?') }}
				</legend>
				<NcTextField :value.sync="destinationPath"
					:helper-text="t('files_sharing', 'The uploaded files are visible only to you unless you choose to share them.')"
					:label-outside="true"
					:placeholder="t('files_sharing', 'Select a destination')"
					:readonly="true"
					:required="false"
					:show-trailing-button="destinationPath !== context.path"
					:trailing-button-icon="'undo'"
					:trailing-button-label="t('files_sharing', 'Revert to default')"
					@click="onPickDestination"
					@trailing-button-click="destination = ''">
					<IconFolder :size="18" />
				</NcTextField>
			</fieldset>
		</form>

		<!-- Controls -->
		<template #actions>
			<!-- Cancel the creation -->
			<NcButton :aria-label="t('files_sharing', 'Cancel')"
				:title="t('files_sharing', 'Cancel the file request creation')"
				data-cy-conflict-picker-cancel
				type="tertiary"
				@click="onCancel">
				{{ t('files_sharing', 'Cancel') }}
			</NcButton>

			<!-- Align right -->
			<span class="dialog__actions-separator" />

			<NcButton :aria-label="t('files_sharing', 'Continue')"
				data-cy-conflict-picker-skip
				@click="onPageNext">
				<template #icon>
					<IconNext :size="20" />
				</template>
				{{ t('files_sharing', 'Continue') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { Folder, Node } from '@nextcloud/files'

import { defineComponent } from 'vue'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { translate } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import IconFolder from 'vue-material-design-icons/Folder.vue'
import IconNext from 'vue-material-design-icons/ArrowRight.vue'

enum STEP {
	INIT = 0,
	FINISH = 1,
}

export default defineComponent({
	name: 'NewFileRequestDialog',

	components: {
		IconFolder,
		IconNext,
		NcButton,
		NcDialog,
		NcNoteCard,
		NcTextField,
	},

	props: {
		context: {
			type: Object as PropType<Folder>,
			required: true,
		},
		content: {
			type: Array as PropType<Node[]>,
			required: true,
		},
	},

	setup() {
		return {
			t: translate,
		}
	},

	data() {
		return {
			currentStep: STEP.INIT,
			label: '',
			destination: '',
		}
	},

	computed: {
		destinationPath: {
			get(): string {
				return this.destination
					|| this.context.path
					|| '/'
			},
			set(value: string) {
				this.destination = value
			},
		},
	},

	methods: {
		onPageNext() {
			this.currentStep = STEP.FINISH
		},

		onPickDestination() {
			const filepicker = getFilePickerBuilder(this.t('files_sharing', 'Select a destination'))
				.addMimeTypeFilter('httpd/unix-directory')
				.allowDirectories(true)
				.addButton({
					label: this.t('files_sharing', 'Select'),
					callback: this.onPickedDestination,
				})
				.setFilter(node => node.path !== '/')
				.startAt(this.destinationPath)
				.build()
			try {
				filepicker.pick()
			} catch (e) {
				// ignore cancel
			}
		},
		onPickedDestination(nodes: Node[]) {
			const node = nodes[0]
			if (node) {
				this.destination = node.path
			}
		},

		onCancel() {
			this.$emit('close')
		},

		onSubmit() {
			this.$emit('submit')
		},
	},
})
</script>

<style scoped lang="scss">
.file-request-dialog {
	--margin: 36px;
	--secondary-margin: 18px;

	&__header {
		position: sticky;
		z-index: 10;
		top: 0;
		margin: 0 var(--margin);
	}

	&__form {
		position: relative;
		overflow: auto;
		padding: 0 var(--margin);
		// overlap header bottom padding
		margin-top: calc(-1 * var(--secondary-margin));
		padding-bottom: var(--margin);
	}

	fieldset {
		display: flex;
		width: 100%;
		margin-top: calc(var(--secondary-margin) * 1.5);

		:deep(legend) {
			display: flex;
			align-items: center;
			width: 100%;
		}
	}

	:deep(.dialog__actions) {
		width: auto;
		margin-inline: 12px;
		// align left and remove margin
		margin-left: 0;
		span.dialog__actions-separator {
			margin-left: auto;
		}
	}

	:deep(.input-field__helper-text-message) {
		// reduce helper text standing out
		color: var(--color-text-maxcontrast);
	}
}
</style>
