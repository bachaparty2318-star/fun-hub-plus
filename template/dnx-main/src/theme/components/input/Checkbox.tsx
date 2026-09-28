import type { Theme, Components } from '@mui/material/styles';
import IconifyIcon from 'components/base/IconifyIcon';
import { boxClasses } from '@mui/material';

const Checkbox: Components<Omit<Theme, 'components'>>['MuiCheckbox'] = {
  defaultProps: {
    icon: <IconifyIcon icon="mdi:checkbox-blank" />,
    checkedIcon: <IconifyIcon icon="mdi:checkbox-marked" />,
    indeterminateIcon: <IconifyIcon icon="mdi:indeterminate-check-box" />,
  },
  styleOverrides: {
    root: ({ theme }) => ({
      color: theme.palette.neutral.lighter,
    }),
    sizeMedium: ({ theme }) => ({
      padding: theme.spacing(0.75),
      [`& .${boxClasses.root}`]: {
        fontSize: theme.typography.h5.fontSize,
      },
    }),
    sizeSmall: ({ theme }) => ({
      [`& .${boxClasses.root}`]: {
        fontSize: theme.typography.h6.fontSize,
      },
    }),
  },
};

export default Checkbox;
