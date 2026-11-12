import * as ImagePicker from 'expo-image-picker';
import { Platform } from 'react-native';
import { api } from '../api/client';
export async function uploadImage(path:string) {
 const result=await ImagePicker.launchImageLibraryAsync({mediaTypes:['images'],quality:.8});if(result.canceled)return null;
 const asset=result.assets[0];if(asset.fileSize&&asset.fileSize>5*1024*1024)throw new Error('Ukuran foto maksimal 5 MB.');const form=new FormData();
 if(Platform.OS==='web'){const blob=await (await fetch(asset.uri)).blob();form.append('image',blob,asset.fileName||'image.jpg');}
 else form.append('image',{uri:asset.uri,name:asset.fileName||'image.jpg',type:asset.mimeType||'image/jpeg'} as any);
 const data=await api(path,'POST',form);return {data};
}
