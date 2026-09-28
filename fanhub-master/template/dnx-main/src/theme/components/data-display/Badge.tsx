import type { Theme, Components } from '@mui/material/styles';

const Badge: Components<Omit<Theme, 'components'>>['MuiBadge'] = {
  styleOverrides: {
    root: {},
    badge: {
      top: 6,
      right: 5,
    },
  },
};

export default Badge;
