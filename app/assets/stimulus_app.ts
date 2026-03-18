import { startStimulusApp } from '@symfony/stimulus-bridge';
import type { Application } from '@hotwired/stimulus';

const startApp = startStimulusApp as unknown as () => Application;

export const app = startApp();