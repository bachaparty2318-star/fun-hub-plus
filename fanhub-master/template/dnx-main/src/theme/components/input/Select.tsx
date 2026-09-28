import { boxClasses, inputBaseClasses } from '@mui/material';
import type { Theme, Components } from '@mui/material/styles';

const Select: Components<Omit<Theme, 'components'>>['MuiSelect'] = {
  styleOverrides: {
    root: ({ theme }) => ({
      padding: 0,
      [`&.${inputBaseClasses.root}`]: {
        backgroundColor: 'transparent !important',
      },
      [`& .${boxClasses.root}`]: {
        fontSize: theme.typography.h4.fontSize,
      },
    }),
    select: ({ theme }) => ({
      padding: theme.spacing(1),
      paddingLeft: theme.spacing(1.25),
      paddingRight: '0 !important',
      backgroundColor: 'transparent !important',
      fontSize: theme.typography.caption.fontSize,
      color: theme.palette.text.primary,
      fontWeight: 600,
    }),
  },
};

export default Select;
