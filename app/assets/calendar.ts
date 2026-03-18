import { app } from './stimulus_app';
import CalendarController from './controllers/calendar_controller';
import './styles/calendar-page.scss';

app.register('calendar', CalendarController);
