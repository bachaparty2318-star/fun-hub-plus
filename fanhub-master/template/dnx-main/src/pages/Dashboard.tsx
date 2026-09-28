import Box from '@mui/material/Box';
import Stack from '@mui/material/Stack';
import Footer from 'components/common/Footer';
import TaskToday from 'components/sections/dashboard/TaskToday';
import WeekCalendar from 'components/sections/dashboard/WeekCalendar';
import Activity from 'components/sections/dashboard/activity/Activity';
import RunningTask from 'components/sections/dashboard/running-task/RunningTask';
import MonthlyMentors from 'components/sections/dashboard/monthly-mentors/MonthlyMentors';
import UpcomingTask from 'components/sections/dashboard/upcoming-task/UpcomingTask';
import TaskOverview from 'components/sections/dashboard/task-overview/TaskOverview';

const Dashboard = () => {
  return (
    <Stack direction={{ xs: 'column', md: 'row' }}>
      <Stack p={3.5} spacing={3.5} direction="column" width={{ xs: 1, md: 'calc(100% - 460px)' }}>
        <Stack
          width={1}
          spacing={3.5}
          direction={{ xs: 'column', sm: 'row', md: 'column', xl: 'row' }}
        >
          <RunningTask />
          <Activity />
        </Stack>

        <MonthlyMentors />
        <UpcomingTask />
        <TaskOverview />

        <Footer sx={{ display: { xs: 'none', md: 'block' } }} />
      </Stack>

      <Box
        width={{ xs: 1, md: 460 }}
        height={{ xs: 'auto', md: 'calc(100vh - 90px)' }}
        overflow="scroll"
        bgcolor="info.main"
        position="sticky"
        top={90}
        sx={{
          '&:hover, &:focus': {
            '&::-webkit-scrollbar-thumb': {
              visibility: 'visible',
            },
          },
        }}
      >
        <Stack direction="column" p={3.5} spacing={3.5} width={1}>
          <WeekCalendar />
          <TaskToday />
        </Stack>

        <Footer sx={{ display: { xs: 'block', md: 'none' } }} />
      </Box>
    </Stack>
  );
};

export default Dashboard;
