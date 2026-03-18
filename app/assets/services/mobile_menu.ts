const getMenuToggleButton = (): HTMLButtonElement | null => {
	return document.querySelector<HTMLButtonElement>('[data-app-menu-toggle]');
};

const getMenuOverlay = (): HTMLElement | null => {
	return document.querySelector<HTMLElement>('[data-app-menu-overlay]');
};

const getLeftSidebar = (): HTMLElement | null => {
	return document.getElementById('app-sidebar-left');
};

const isMobileViewport = (): boolean => {
	return window.innerWidth < 768;
};

const setMenuOpenState = (isOpen: boolean): void => {
	document.body.classList.toggle('has-open-menu', isOpen);

	const menuToggleButton = getMenuToggleButton();
	const menuOverlay = getMenuOverlay();
	const leftSidebar = getLeftSidebar();

	if (menuToggleButton) {
		menuToggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
	}

	if (menuOverlay) {
		menuOverlay.classList.toggle('is-open', isOpen);
	}

	if (leftSidebar) {
		leftSidebar.classList.toggle('is-open', isOpen);
	}
};

const initializeAppMenu = (): void => {
	document.addEventListener('click', (event: MouseEvent) => {
		const target = event.target;

		if (!(target instanceof Element)) {
			return;
		}

		if (target.closest('[data-app-menu-toggle]')) {
			const nextState = !document.body.classList.contains('has-open-menu');
			setMenuOpenState(nextState);
			return;
		}

		if (target.closest('[data-app-menu-overlay]')) {
			setMenuOpenState(false);
			return;
		}

		if (target.closest('#app-sidebar-left a') && isMobileViewport()) {
			setMenuOpenState(false);
		}
	});

	document.addEventListener('keydown', (event: KeyboardEvent) => {
		if (event.key === 'Escape') {
			setMenuOpenState(false);
		}
	});

	window.addEventListener('resize', () => {
		if (!isMobileViewport()) {
			setMenuOpenState(false);
		}
	});

	document.addEventListener('turbo:load', () => {
		if (!isMobileViewport()) {
			setMenuOpenState(false);
		} else {
			const menuToggleButton = getMenuToggleButton();

			if (menuToggleButton) {
				menuToggleButton.setAttribute('aria-expanded', 'false');
			}
			if (document.body.classList.contains('has-open-menu')) {
				setMenuOpenState(false);
			}
		}
	});
};

export default initializeAppMenu;
