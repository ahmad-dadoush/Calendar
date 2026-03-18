type LoaderElements = {
	host: HTMLElement;
	loading: HTMLElement;
	overlay: HTMLElement;
};

class GlobalLoader {
	private static instance: GlobalLoader | null = null;

	private activeRequests = 0;

	private readonly targets = new Set<LoaderElements>();

	private constructor() {}

	static getInstance(): GlobalLoader {
		if (null === GlobalLoader.instance) {
			GlobalLoader.instance = new GlobalLoader();
		}

		return GlobalLoader.instance;
	}

	register(elements: LoaderElements): void {
		this.targets.add(elements);
		this.syncTarget(elements);
	}

	unregister(elements: LoaderElements): void {
		this.targets.delete(elements);
	}

	begin(): void {
		this.activeRequests += 1;
		this.sync();
	}

	end(): void {
		this.activeRequests = Math.max(0, this.activeRequests - 1);
		this.sync();
	}

	private sync(): void {
		for (const elements of this.targets) {
			this.syncTarget(elements);
		}
	}

	private syncTarget(elements: LoaderElements): void {
		const isBusy = this.activeRequests > 0;

		elements.loading.hidden = !isBusy;
		elements.overlay.hidden = !isBusy;

		if (isBusy) {
			elements.host.setAttribute('aria-busy', 'true');
			return;
		}

		elements.host.removeAttribute('aria-busy');
	}
}

export type { LoaderElements };
export default GlobalLoader;
