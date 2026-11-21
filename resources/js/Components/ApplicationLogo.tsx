import { ImgHTMLAttributes } from 'react';
import logoMmj from '../../images/logo-mmj.png';

export default function ApplicationLogo(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src={logoMmj}
            alt="MMJ Driver"
            {...props}
        />
    );
}
