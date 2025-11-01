<template>
	<k-button
		v-bind="$props"
		:icon="currentIcon"
		:disabled="isLoading"
		@click="onClick"
	/>
</template>

<script>
import { usePanel } from "kirbyuse"

export default {
	extends: "k-view-button",
	props: {
		jobId: String
	},
	data() {
		return {
			isLoading: false
		}
	},
	computed: {
		currentIcon() {
			return this.isLoading ? "loader" : this.icon
		}
	},
	methods: {
		async onClick() {
			if (this.isLoading) {
				return
			}

			this.isLoading = true
			const panel = usePanel()

			try {
				// if jobId is null, refresh all jobs
				if (!this.jobId) {
					await panel.post('join/refresh-all')
					panel.notification.success(panel.t("join.buttons.refresh.all.success"))
				} else {
					await panel.post(`join/${this.jobId}`)
					panel.notification.success(panel.t("join.buttons.refresh.success"))
				}

				await panel.reload()
			} catch (error) {
				panel.notification.error({
					message: error.message || panel.t("join.buttons.refresh.error")
				})
			} finally {
				this.isLoading = false
			}
		}
	}
}
</script>
