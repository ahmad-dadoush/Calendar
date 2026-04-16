import { Controller } from '@hotwired/stimulus';
import AjaxClient from '../services/ajax_client';
import GlobalLoader from '../services/global_loader';

type SendRemindersResponse = {
	message: string;
	sent: number;
	failed: number;
	sentEmails: string[];
	failedEmails: string[];
};

export default class extends Controller {
	static targets = ['sendButton', 'feedback'];

	declare readonly sendButtonTarget: HTMLButtonElement;
	declare readonly feedbackTarget: HTMLElement;

	private readonly loader = GlobalLoader.getInstance();
	private readonly ajax = new AjaxClient(this.loader);

	/** Sends reminder emails for all calendar items due today. */
	async sendAll(event: Event): Promise<void> {
		event.preventDefault();

		this.sendButtonTarget.disabled = true;

		const response = await this.ajax.request('/api/reminders/send-all', {
			method: 'POST',
		});

		if (!response.ok) {
			this.sendButtonTarget.disabled = false;
			await this.ajax.handleApiError(response);
			return;
		}

		const data = (await response.json()) as SendRemindersResponse;
		this.displayFeedback(data);
		this.sendButtonTarget.disabled = false;
	}

	/** Displays detailed feedback about sent and failed reminders. */
	private displayFeedback(data: SendRemindersResponse): void {
		const feedback = this.feedbackTarget;
		feedback.innerHTML = '';

		// Create message element
		const messageDiv = document.createElement('div');
		messageDiv.className = 'alert alert-success';
		messageDiv.textContent = data.message;
		feedback.appendChild(messageDiv);

		// Create sent emails list
		if (data.sentEmails.length > 0) {
			const sentDiv = document.createElement('div');
			sentDiv.className = 'u-mt-3';
			sentDiv.innerHTML = `
				<h4>✓ Erfolgreich versendet (${data.sentEmails.length})</h4>
				<ul>
					${data.sentEmails.map(email => `<li>${this.escapeHtml(email)}</li>`).join('')}
				</ul>
			`;
			feedback.appendChild(sentDiv);
		}

		// Create failed emails list
		if (data.failedEmails.length > 0) {
			const failedDiv = document.createElement('div');
			failedDiv.className = 'alert alert-danger u-mt-3';
			failedDiv.innerHTML = `
				<h4>✗ Fehler beim Versand (${data.failedEmails.length})</h4>
				<ul>
					${data.failedEmails.map(email => `<li>${this.escapeHtml(email)}</li>`).join('')}
				</ul>
			`;
			feedback.appendChild(failedDiv);
		}

		// Show feedback
		feedback.classList.remove('u-hidden');

		// Auto-hide after 10 seconds
		setTimeout(() => {
			feedback.classList.add('u-hidden');
		}, 10000);
	}

	/** Escapes HTML special characters to prevent XSS. */
	private escapeHtml(text: string): string {
		const map: Record<string, string> = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;',
		};
		return text.replace(/[&<>"']/g, char => map[char]);
	}
}
