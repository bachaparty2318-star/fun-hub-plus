import { SxProps } from '@mui/material';
import Link from '@mui/material/Link';
import Typography from '@mui/material/Typography';

interface FooterProps extends React.HTMLAttributes<HTMLElement> {
  sx?: SxProps;
}

const Footer = ({ sx, ...rest }: FooterProps) => {
  return (
    <Typography
      mt={0.5}
      px={1}
      pb={{ xs: 3, md: 0 }}
      color="text.secondary"
      variant="body2"
      textAlign={{ xs: 'center', md: 'right' }}
      letterSpacing={0.5}
      fontWeight={500}
      sx={sx}
      {...rest}
    >
      Made with ❤️ by{' '}
      <Link href="https://themewagon.com/" target="_blank" rel="noreferrer">
        {'ThemeWagon'}
      </Link>
    </Typography>
  );
};

export default Footer;
