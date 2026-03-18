import { Controller } from '@hotwired/stimulus';
import AjaxClient from '../services/ajax_client';
import GlobalLoader, { type LoaderElements } from '../services/global_loader';
import { formatDate } from '../utils/date';

type CalendarItem = {
	id: number;
	date: string | null;
	formattedDate: string | null;
	description: string | null;
	reminderDays: number | null;
};

type CalendarListResponse = {
	items: CalendarItem[];
};

const apiBaseUrl = '/api/calendar-items';

// Coordinates form actions and table rendering for calendar items.
export default class extends Controller {
	static targets = [
		'form',
		'day',
		'month',
		'description',
		'reminderDays',
		'itemId',
		'tableBody',
		'emptyState',
		'tableWrapper',
		'loading',
		'overlay',
		'cancelButton',
	];

	declare readonly formTarget: HTMLFormElement;
	declare readonly dayTarget: HTMLInputElement;
	declare readonly monthTarget: HTMLInputElement;
	declare readonly descriptionTarget: HTMLInputElement;
	declare readonly reminderDaysTarget: HTMLSelectElement;
	declare readonly itemIdTarget: HTMLInputElement;
	declare readonly tableBodyTarget: HTMLTableSectionElement;
	declare readonly emptyStateTarget: HTMLElement;
	declare readonly tableWrapperTarget: HTMLElement;
	declare readonly loadingTarget: HTMLElement;
	declare readonly overlayTarget: HTMLElement;
	declare readonly cancelButtonTarget: HTMLButtonElement;
	declare readonly hasCancelButtonTarget: boolean;

	private readonly loader = GlobalLoader.getInstance();

	private readonly ajax = new AjaxClient(this.loader);

	private registeredLoaderElements: LoaderElements | null = null;

	/** Initializes loader bindings and fetches initial data. */
	connect(): void {
		this.registerLoaderElements();
		void this.loadItems();
	}

	/** Removes this controller's loader bindings. */
	disconnect(): void {
		if (this.registeredLoaderElements) {
			this.loader.unregister(this.registeredLoaderElements);
			this.registeredLoaderElements = null;
		}
	}

	/** Creates or updates a calendar item from the form values. */
	async submit(event: Event): Promise<void> {
		event.preventDefault();

		const payload = this.buildPayload();
		if (null === payload) {
			alert('Bitte gib ein gültiges Datum ein.');
			return;
		}

		const itemId = this.itemIdTarget.value.trim();
		const isUpdate = '' !== itemId;
		const url = isUpdate ? `${apiBaseUrl}/${itemId}` : apiBaseUrl;
		const method = isUpdate ? 'PATCH' : 'POST';

		const response = await this.ajax.request(url, { method, payload });

		if (!response.ok) {
			await this.ajax.handleApiError(response);
			return;
		}

		this.resetForm();
		await this.loadItems();
	}

	/** Loads one item and fills the form for editing. */
	async edit(event: Event): Promise<void> {
		const button = event.currentTarget as HTMLButtonElement | null;
		const id = button?.dataset.id;

		if (!id) {
			return;
		}

		const response = await this.ajax.request(`${apiBaseUrl}/${id}`, { method: 'GET' });

		if (!response.ok) {
			await this.ajax.handleApiError(response);
			return;
		}

		const item = (await response.json()) as CalendarItem;
		const [, month, day] = (item.date ?? '').split('-');

		this.itemIdTarget.value = String(item.id);
		this.dayTarget.value = day ?? '';
		this.monthTarget.value = month ?? '';
		this.descriptionTarget.value = item.description ?? '';
		this.reminderDaysTarget.value = null === item.reminderDays ? '' : String(item.reminderDays);

		const submitButton = this.getSubmitButton();
		if (submitButton) {
			submitButton.textContent = 'Aktualisieren';
		}

		if (this.hasCancelButtonTarget) {
			this.cancelButtonTarget.hidden = false;
		}

		this.dayTarget.focus();
	}

	/** Deletes an item after user confirmation. */
	async delete(event: Event): Promise<void> {
		const button = event.currentTarget as HTMLButtonElement | null;
		const id = button?.dataset.id;

		if (!id) {
			return;
		}

		if (!window.confirm('Termin wirklich löschen?')) {
			return;
		}

		const response = await this.ajax.request(`${apiBaseUrl}/${id}`, { method: 'DELETE' });

		if (!response.ok) {
			await this.ajax.handleApiError(response);
			return;
		}

		if (this.itemIdTarget.value === id) {
			this.resetForm();
		}

		await this.loadItems();
	}

	/** Resets the form to create mode. */
	reset(): void {
		this.resetForm();
	}

	/** Fetches the current calendar list and renders it. */
	private async loadItems(): Promise<void> {
		const response = await this.ajax.request(apiBaseUrl, { method: 'GET' });

		if (!response.ok) {
			await this.ajax.handleApiError(response);
			return;
		}

		const data = (await response.json()) as CalendarListResponse;
		this.renderItems(data.items);
	}

	/** Rebuilds the table body and empty-state visibility. */
	private renderItems(items: CalendarItem[]): void {
		this.tableBodyTarget.innerHTML = '';

		for (const item of items) {
			const row = document.createElement('tr');

			const dateCell = document.createElement('td');
			dateCell.textContent = item.formattedDate ?? formatDate(item.date);

			const descriptionCell = document.createElement('td');
			descriptionCell.textContent = item.description ?? '-';

			const reminderCell = document.createElement('td');
			reminderCell.textContent = this.reminderLabel(item.reminderDays);

			const actionsCell = document.createElement('td');
			const actionsWrap = document.createElement('div');
			actionsWrap.className = 'app-calendar-actions';

			const editButton = document.createElement('button');
			editButton.type = 'button';
			editButton.textContent = 'Bearbeiten';
			editButton.className = 'app-calendar-action-link';
			editButton.dataset.action = 'click->calendar#edit';
			editButton.dataset.id = String(item.id);

			const separator = document.createElement('span');
			separator.className = 'app-calendar-separator';
			separator.textContent = '|';

			const deleteButton = document.createElement('button');
			deleteButton.type = 'button';
			deleteButton.textContent = 'Löschen';
			deleteButton.className = 'app-calendar-action-link';
			deleteButton.dataset.action = 'click->calendar#delete';
			deleteButton.dataset.id = String(item.id);

			actionsWrap.append(editButton, separator, deleteButton);
			actionsCell.append(actionsWrap);

			row.append(dateCell, descriptionCell, reminderCell, actionsCell);
			this.tableBodyTarget.append(row);
		}

		const hasItems = items.length > 0;
		this.emptyStateTarget.hidden = hasItems;
		this.tableWrapperTarget.hidden = !hasItems;
	}

	/** Builds a validated payload for API requests. */
	private buildPayload(): { date: string; description: string; reminderDays: number | null } | null {
		// Keep client-side date checks strict to avoid sending invalid combinations.
		const day = Number.parseInt(this.dayTarget.value, 10);
		const month = Number.parseInt(this.monthTarget.value, 10);
		const year = new Date().getFullYear();

		if (!Number.isInteger(day) || !Number.isInteger(month)) {
			return null;
		}

		const date = new Date(year, month - 1, day);
		if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
			return null;
		}

		const reminderDaysValue = this.reminderDaysTarget.value;

		return {
			date: `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
			description: this.descriptionTarget.value,
			reminderDays: '' === reminderDaysValue ? null : Number.parseInt(reminderDaysValue, 10),
		};
	}

	/** Clears inputs and restores default submit label. */
	private resetForm(): void {
		this.formTarget.reset();
		this.itemIdTarget.value = '';

		const submitButton = this.getSubmitButton();
		if (submitButton) {
			submitButton.textContent = 'Speichern';
		}

		if (this.hasCancelButtonTarget) {
			this.cancelButtonTarget.hidden = true;
		}
	}

	/** Returns the form submit button if present. */
	private getSubmitButton(): HTMLButtonElement | null {
		return this.formTarget.querySelector('button[type="submit"]');
	}

	/** Registers local loading UI elements in the shared loader. */
	private registerLoaderElements(): void {
		// Register this controller's loader nodes in the shared loading service.
		const elements: LoaderElements = {
			host: this.element as HTMLElement,
			loading: this.loadingTarget,
			overlay: this.overlayTarget,
		};

		this.loader.register(elements);
		this.registeredLoaderElements = elements;
	}

	/** Maps reminder days to a localized label. */
	private reminderLabel(reminderDays: number | null): string {
		const labels: Record<number, string> = {
			1: '1 Tag',
			2: '2 Tage',
			4: '4 Tage',
			7: '1 Woche',
			14: '2 Wochen',
		};

		if (null === reminderDays) {
			return '-';
		}

		return labels[reminderDays] ?? '-';
	}

}
