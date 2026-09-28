import type { Theme, Components } from '@mui/material/styles';

const CardMedia: Components<Omit<Theme, 'components'>>['MuiCardMedia'] = {
  styleOverrides: {
    root: {},
    img: ({ theme }) => ({
      borderRadius: Number(theme.shape.borderRadius) * 2.5,
    }),
  },
};

export default CardMedia;
