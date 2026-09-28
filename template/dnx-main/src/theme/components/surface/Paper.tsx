import { menuClasses } from '@mui/material';
import type { Theme, Components } from '@mui/material/styles';

const Paper: Components<Omit<Theme, 'components'>>['MuiPaper'] = {
  styleOverrides: {
    root: ({ theme }) => ({
      padding: theme.spacing(2.5),
      backgroundColor: theme.palette.info.lighter,
      borderRadius: Number(theme.shape.borderRadius) * 3,
      overflow: 'hidden',
      boxShadow: 'none',

      [`&.${menuClasses.paper}`]: {
        padding: 0,
        boxShadow: theme.customShadows[0],
      },
    }),
  },
};

export default Paper;
