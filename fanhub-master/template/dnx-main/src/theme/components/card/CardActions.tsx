import type { Theme, Components } from '@mui/material/styles';

const CardActions: Components<Omit<Theme, 'components'>>['MuiCardActions'] = {
  styleOverrides: {
    root: {
      padding: 0,
    },
  },
};

export default CardActions;
