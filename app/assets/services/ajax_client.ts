import GlobalLoader from './global_loader';

type AjaxRequestOptions = {
	method: string;
	payload?: unknown;
};

class AjaxClient {
	/**
	 * @param loader Shared loader instance used to track request activity.
	 */
	constructor(private readonly loader: GlobalLoader) {}

	/**
	 * @param url Endpoint URL.
	 * @param options Request configuration including method and optional payload.
	 * @returns The fetch Response from the server.
	 */
	async request(url: string, options: AjaxRequestOptions): Promise<Response> {
		this.loader.begin();

		const init: RequestInit = {
			method: options.method,
			headers: {
				Accept: 'application/json',
			},
		};

		if (undefined !== options.payload) {
			init.headers = {
				...init.headers,
				'Content-Type': 'application/json',
			};
			init.body = JSON.stringify(options.payload);
		}

		try {
			return await fetch(url, init);
		} finally {
			this.loader.end();
		}
	}

	/**
	 * @param response Failed API response.
	 * @returns Resolves when the user-facing error has been shown.
	 */
	async handleApiError(response: Response): Promise<void> {
		alert(await this.getErrorMessage(response));
	}

	/**
	 * @param response Failed API response.
	 * @returns Human-readable error message parsed from API response.
	 */
	private async getErrorMessage(response: Response): Promise<string> {
		let message = 'Fehler bei der Anfrage.';

		try {
			const data = (await response.json()) as { message?: string; errors?: Record<string, string[]> };
			if (data.message) {
				message = data.message;
			} else if (data.errors) {
				const flatErrors = Object.values(data.errors).flat();
				if (flatErrors.length > 0) {
					message = flatErrors.join('\n');
				}
			}
		} catch {
			// Ignore parse errors and use fallback message.
		}

		return message;
	}
}

export default AjaxClient;
